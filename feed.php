<?php

if (!empty($_GET['url'])) {
    mb_internal_encoding("UTF-8");
    setlocale(LC_ALL, 'ru_RU');

    require_once 'simple_html_dom.php';

    $url = filter_var($_GET['url'], FILTER_SANITIZE_URL);
    if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
        die('ERROR: Not a valid URL');
    } else {
        $parse_url = parse_url($url);
        $site = $parse_url['scheme'] . '://' . $parse_url['host'];
    }
    $title = strip_tags($_GET['title']);
    $link = strip_tags($_GET['link']);
    $author = strip_tags($_GET['author']);
    $date = strip_tags($_GET['date']);
    $image = strip_tags($_GET['image']);
    $content = strip_tags($_GET['content']);
    $content_filters = $_GET['content_filters'];

    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $html = curl_exec($curl);
        curl_close($curl);
    } else {
        $html = file_get_contents($url);
    }

    $items = array(
        "title" => array(),
        "link" => array(),
        "content" => array()
    );
    // Добавляем опциональное поле даты если есть что искать
    if (!empty($date)) {
        $items["date"] = array();
    }
    if (!empty($author)) {
        $items["author"] = array();
    }
    if (!empty($image)) {
        $items["image"] = array();
    }
    $count_fields = count($items);

    $html_dom = str_get_html($html);
    if ($html_dom) {
        foreach ($html_dom->find($title) as $element)
            $items['title'][] = trim(html_entity_decode($element->plaintext));
        foreach ($html_dom->find($link) as $element)
            $items['link'][] = filter_var($element->href, FILTER_SANITIZE_URL);
        foreach ($html_dom->find($author) as $element)
            $items['author'][] = trim(html_entity_decode($element->plaintext));
        foreach ($html_dom->find($date) as $element)
            $items['date'][] = trim(html_entity_decode($element->plaintext));
        foreach ($html_dom->find($image) as $element)
            $items['image'][] = filter_var($element->src, FILTER_SANITIZE_URL);
        foreach ($html_dom->find($content) as $element)
            $items['content'][] = trim($element->innertext);
    }

    // Вычисляем совпадает ли количество найденных элементов
    $count_items = count($items['title']);
    if ((count($items, COUNT_RECURSIVE) - $count_fields) / $count_items != $count_fields) {
        $discrepancy = True;
        if (!isset($_GET['parse'])) die('ERROR: The discrepancy between the number of items found');
    } else {
        $discrepancy = False;
    }
    // Заголовок сайта
    $site_title = $html_dom->find('title', 0)->plaintext;

    // Выдаем полученные данные ввиде RSS
    if (!isset($_GET['parse'])) {
        header('Content-Type: text/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
        echo '<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">' . PHP_EOL;
        echo '<channel>' . PHP_EOL;
        echo '<title>Site feed - ' . $site_title . '</title>' . PHP_EOL;
        echo '<description>Site feed "' . $site_title . '" through Site to RSS proxy by Nomadic</description>' . PHP_EOL;
        echo '<link>' . $site . '</link>' . PHP_EOL;
        echo '<lastBuildDate>' . date('r') . '</lastBuildDate>' . PHP_EOL;
        for ($i = 0; $i < $count_items; $i++) {
            echo '<item>' . PHP_EOL;
            $title = preg_split("/\r\n|\n|\r/", $items['title'][$i], -1, PREG_SPLIT_NO_EMPTY);
            echo '<title>' . $title[0] . '</title>' . PHP_EOL;
            if (!empty($items['author'][$i])) {
                echo '<author>' . preg_replace('/\s+/', ' ', $items['author'][$i]) . '</author>' . PHP_EOL;
            }
            if (!empty($items['date'][$i])) {
                // Если нельзя преобразовать строку даты
                if (!strtotime($items['date'][$i])) {
                    // Заменяем русские названия
                    $items['date'][$i] = str_ireplace("сегодня", date('Y-m-d'), $items['date'][$i]);
                    $items['date'][$i] = str_ireplace("вчера", date('Y-m-d', strtotime('-1 days')), $items['date'][$i]);
                    $items['date'][$i] = str_ireplace("минут", ' minutes ago', $items['date'][$i]);
                    $items['date'][$i] = str_ireplace("час", ' hours ago', $items['date'][$i]);
                    $items['date'][$i] = str_ireplace("день", ' day ago', $items['date'][$i]);
                    $items['date'][$i] = str_ireplace("дней", ' days ago', $items['date'][$i]);
                    $items['date'][$i] = str_ireplace("недел", ' weeks ago', $items['date'][$i]);
                    $items['date'][$i] = str_ireplace("месяц", ' months ago', $items['date'][$i]);
                    $items['date'][$i] = str_ireplace("год", ' years ago', $items['date'][$i]);
                    $items['date'][$i] = str_ireplace("лет", ' years ago', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(янв\S*)/i", 'january', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(фев\S*)/i", 'february', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(мар\S*)/i", 'march', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(апр\S*)/i", 'april', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(май\S*)/i", 'may', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(июн\S*)/i", 'june', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(июл\S*)/i", 'july', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(авг\S*)/i", 'august', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(сен\S*)/i", 'september', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(окт\S*)/i", 'october', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(ноя\S*)/i", 'november', $items['date'][$i]);
                    $items['date'][$i] = preg_replace("/(дек\S*)/i", 'december', $items['date'][$i]);
                    // Удаляем лишние русские символы
                    $items['date'][$i] = preg_replace("![А-Я]!i", "", $items['date'][$i]);
                }
                echo '<pubDate>' . date('r', strtotime($items['date'][$i])) . '</pubDate>' . PHP_EOL;
            }
            // Заменяем относительные ссылки на абсолютные
            $items['link'][$i] = preg_replace('/(^\/[^\s]+)/', $site . '$1', $items['link'][$i]);
            echo '<guid isPermaLink="true">' . $items['link'][$i] . '</guid>' . PHP_EOL;
            echo '<link>' . $items['link'][$i] . '</link>' . PHP_EOL;
            if (!empty($items['image'][$i])) {
                $items['image'][$i] = preg_replace('/(^\/[^\s]+)/', $site . '$1', $items['image'][$i]);
                echo '<media:content url="' . $items['image'][$i] . '" medium="image" />' . PHP_EOL;
            }
            // Удаляем ненужные строки
            $filters = preg_split("/\r\n|\n|\r/", $content_filters, -1, PREG_SPLIT_NO_EMPTY);
            if (count($filters) > 0) {
                foreach ($filters as $search) {
                    $search = trim($search);
                    if (preg_match('/^\/.+\/[a-z]*/i', $search)) {
                        // Если фильтр регулярное выражение
                        $items['content'][$i] = preg_replace($search . 'u', '', $items['content'][$i]);
                    } else {
                        // Если фильтр строка замены
                        $items['content'][$i] = str_replace($search, '', $items['content'][$i]);
                    }
                }
            }
            // Заменяем относительные ссылки на абсолютные в содержимом
            $items['content'][$i] = preg_replace("/(<\s*(a|img)\s+[^>]*(href|src)\s*=\s*[\"'])(?!http)([^\"'>]+)([\"'>]+)/", '$1' . $site . '/$4$5', $items['content'][$i]);
            // Удаляем пустые строки
            $items['content'][$i] = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $items['content'][$i]);
            echo '<description><![CDATA[' . trim($items['content'][$i]) . ']]></description>' . PHP_EOL;
            echo '</item>' . PHP_EOL;
        }
        echo '</channel>' . PHP_EOL;
        echo '</rss>' . PHP_EOL;
        die();
    }
    $html_dom->clear();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Site to RSS converter</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="keywords" content="Site, RSS, Atom, feed, converter, generator, agregator, convert to, convert, to">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/latest/css/bootstrap.min.css">
</head>
<body style="padding: 20px;">
<div class="container">
    <div class="jumbotron vertical-center">
        <div class="container">
            <h1>Site to RSS
                <small>converter</small>
            </h1>
            <br/>
            <p>Enter required options and get RSS feed!</p>
            <form id="tform" class="form-horizontal" role="form" action="feed.php" method="GET">
                <div class="form-group">
                    <div class="input-group input-group-lg">
                        <span class="input-group-addon">Site</span>
                        <input type="url" id="url" name="url" class="form-control search-query"
                               placeholder="URL" <?php if (isset($_GET['url'])) {
                            echo 'value="' . $url . '"';
                        } ?> required>
                        <span class="input-group-btn">
									<input class="btn btn-primary" type="submit" value="Get RSS">
									<input name="parse" class="btn btn-default" type="submit" value="Parse">
								</span>
                    </div>
                    <div class="panel panel-default" style="margin-top: 20px;">
                        <div class="panel-body">
                            <div class="form-group">
                                <label for="title" class="col-sm-3 control-label">Title:</label>
                                <div class="col-sm-4">
                                    <input name="title" id="title" class="form-control"
                                           placeholder="Simple HTML DOM find string" <?php if (isset($_GET['title'])) {
                                        echo 'value="' . $title . '"';
                                    } ?> required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="link" class="col-sm-3 control-label">Link:</label>
                                <div class="col-sm-4">
                                    <input name="link" id="link" class="form-control"
                                           placeholder="Simple HTML DOM find string" <?php if (isset($_GET['link'])) {
                                        echo 'value="' . $link . '"';
                                    } ?> required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="author" class="col-sm-3 control-label">Author:</label>
                                <div class="col-sm-4">
                                    <input name="author" id="author" class="form-control"
                                           placeholder="Simple HTML DOM find string" <?php if (isset($_GET['author'])) {
                                        echo 'value="' . $author . '"';
                                    } ?> >
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="date" class="col-sm-3 control-label">Publish date:</label>
                                <div class="col-sm-4">
                                    <input name="date" id="date" class="form-control"
                                           placeholder="Simple HTML DOM find string" <?php if (isset($_GET['date'])) {
                                        echo 'value="' . $date . '"';
                                    } ?> >
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="date" class="col-sm-3 control-label">Image:</label>
                                <div class="col-sm-4">
                                    <input name="image" id="date" class="form-control"
                                           placeholder="Simple HTML DOM find string" <?php if (isset($_GET['image'])) {
                                        echo 'value="' . $image . '"';
                                    } ?> >
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="content" class="col-sm-3 control-label">Content:</label>
                                <div class="col-sm-4">
                                    <input name="content" id="content" class="form-control"
                                           placeholder="Simple HTML DOM find string" <?php if (isset($_GET['content'])) {
                                        echo 'value="' . $content . '"';
                                    } ?> required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="content_filters" class="col-sm-3 control-label">Filters (string or regex)
                                    for remove unnecessary strings in content:</label>
                                <div class="col-sm-6">
                                    <textarea name="content_filters" id="content_filters" class="form-control"
                                              wrap="off" rows="4"
                                              placeholder="one filter per lines"><?php if (isset($_GET['content_filters'])) {
                                            echo $content_filters;
                                        } ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div> <!-- jumbotron -->
    <?php
    if ($discrepancy) echo '<div class="alert alert-danger" role="alert"><b>ERROR</b>: The discrepancy between the number of items found</div>';
    if (isset($_GET['parse'])) {
        echo "<pre>";
        print_r($items);
        echo "</pre>";
    }
    ?>
    <footer class="navbar">
        <div style="text-align: center;"><p><a href="https://github.com/n0madic/site2rss">GitHub</a> &copy; Nomadic 2016
            </p></div>
    </footer>
</div>
</body>
</html>
