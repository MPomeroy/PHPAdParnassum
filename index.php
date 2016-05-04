<?php
        
        $htmlPre = "
            <!DOCTYPE html>
            <html>
    <head>
        <meta charset=\"UTF-8\">
        <title></title>
    </head>
    <body>
        
        
        <p id='para'>";
        $htmlPost = "    </p>
            <script src=\"https://ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js\"></script>
                        </body>
                    </html>";
        
require_once 'counterpoint.php';

print($htmlPre);
$cp = new counterpoint();
$cp->process();
//$cp->printCompleteCPXML();
$cp->renderCPToFile('test.xml');
$cp->printLog();
print($htmlPost);
