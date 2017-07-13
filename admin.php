<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Site to RSS admin page</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="keywords" content="Site, RSS, Atom, feed, converter, generator, agregator, convert to, convert, to">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/latest/css/bootstrap.min.css">
</head>
<body>
<div class="container">
    <div class="page-header">
        <h1>Site to RSS admin page</h1>
    </div>
    <?php
    error_reporting(E_ERROR);
    ini_set('display_errors', 'On');
    $file = 'gs://site2rss.appspot.com/site2rss.json';
    function save_config()
    {
        global $file, $config_json;
        $write = file_put_contents($file, json_encode($config_json));
        if ($write == false) {
            http_response_code(503);
            echo '<div class="alert alert-danger" role="alert">Config save error!</div>';
        } else {
            echo '<div class="alert alert-success" role="alert">Config saved successfully!</div>';
        }
    }

    $json_file = file_get_contents($file);
    if ($json_file != false) {
        $config_json = json_decode($json_file, JSON_OBJECT_AS_ARRAY);
    } else {
        echo '<div class="alert alert-danger" role="alert">Config file not found!</div>';
    }
    if (isset($_POST['passwd']) && !isset($config_json['password'])) {
        $config_json['password'] = md5($_POST['passwd']);
        save_config();
    } else if (isset($_POST['passwd'])) {
        if (md5($_POST['passwd']) == $config_json['password']) {
            $_SESSION['u_login'] = 'YES';
        } else {
            unset($_SESSION['u_login']);
            session_destroy();
        }
    }

    if (!isset($_SESSION['u_login'])) {
        ?>
        <div class="panel">
            <div class="panel-body">
                <div class="row text-center">
                    <label for="InputPassword1">Please enter password for access:</label>
                    <form class="form-inline" role="form" method="post">
                        <div class="form-group">
                            <label class="sr-only" for="InputPassword2">Password</label>
                            <input type="password" name="passwd" class="form-control" id="InputPassword2"
                                   placeholder="Password">
                        </div>
                        <button type="submit" class="btn btn-large btn-primary">Sign in</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    } else {
        if (!empty($_GET['delete'])) {
            if (isset($config_json[$_GET['delete']])) {
                unset($config_json[$_GET['delete']]);
                save_config();
            }
        }
    }
    if (!isset($config_json['password'])) {
        echo '<div class="alert alert-danger" role="alert">Please set admin password!</div>';
    }
    ?>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title">List of saved configurations:
            </h3>
        </div>
        <div class="panel-body">
            <table class="table table-striped table-hover col-md-4" style="font-size:15px;">
                <thead>
                <tr>
                    <th width="25%">Hash</th>
                    <th>Settings</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php
                if (!empty($json_file) && isset($_SESSION['u_login'])) {
                    $memcache = new Memcache;
                    $memcache->addServer('localhost', 11211);
                    foreach ($config_json as $hash => $config) {
                        if ($hash != 'password') {
                            echo '<tr><td>' . $hash;
                            $date = $memcache->get($hash . '-updated');
                            if ($date != false) echo '<h6>Last updated: ' . $date . '</h6>';
                            echo '</td><td>';
                            foreach ($config as $name => $value) {
                                echo '<strong>' . $name . '</strong> : ' . htmlspecialchars($value) . '<br>';
                            }
                            echo '</td>' . PHP_EOL . '<td>';
                            echo '<a data-toggle="tooltip" title="RSS" class="btn btn-info btn-xs" href="/?get=' . $hash . '" target="_blank"><span class="glyphicon glyphicon-eye-open"></span></a> ';
                            echo '<a data-toggle="tooltip" title="Edit" class="btn btn-primary btn-xs" href="/?edit=' . $hash . '" target="_blank"><span class="glyphicon glyphicon-edit"></span></a> ';
                            echo '<a data-toggle="tooltip" title="Delete" class="btn btn-danger btn-xs" href="/admin?delete=' . $hash . '"><span class="glyphicon glyphicon-remove"></span></a> ';
                            echo '</td></tr>' . PHP_EOL;
                        }
                    }
                }
                ?>
                </tbody>
            </table>
        </div> <!-- panel-body -->
    </div> <!-- panel -->
    <footer class="navbar-bottom">
        <div style="text-align: center;"><p><a href="https://github.com/n0madic/site2rss">GitHub</a> &copy; Nomadic
                2016-2017
            </p></div>
    </footer>
</div> <!-- container -->
</body>
</html>