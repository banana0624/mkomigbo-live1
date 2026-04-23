<?php

// Simple GitHub webhook trigger

file_put_contents("webhook.log", date('c') . " webhook triggered\n", FILE_APPEND);

// run deploy safely
$output = shell_exec("bash /home/mkomigbo/public_html/deploy.sh 2>&1");

file_put_contents("webhook.log", $output . "\n", FILE_APPEND);

echo "OK";
