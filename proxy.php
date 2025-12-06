<?php
/* Function to show error and exit */
function error($msg)
{
	header("HTTP/1.0 502 Bad Gateway");
	header("X-forwarded: errored");
	echo $msg;
	exit;
}

function lg($str)
{
	file_put_contents('logs.txt', $str . PHP_EOL, FILE_APPEND | LOCK_EX);
}

lg("\r\nNEWCONN -----");

/* Hard-coded target host */
$host = "httpbin.io";
$port = 80;
$path = "/anything" . $_SERVER['QUERY_STRING'];

$rqmethod = $_SERVER['REQUEST_METHOD'];
$rqproto = $_SERVER['SERVER_PROTOCOL'];
$rqheaders = apache_request_headers();


$fs = fsockopen($host, $port, $errno, $errstr, 30);
if (!$fs) {
	lg("error fsockopen:     $errno $errstr");
	error("error fsockopen: $errno $errstr");
}

#stream_set_blocking($fs, false);


$rq = "$rqmethod $path $rqproto\r\n";
lg("rq towrite httpline: \"$rq\"");
foreach ($rqheaders as $header => $value) {
	if ($value != "") {
		$rq .= "$header: $value\r\n";
		lg("rq towrite header:   \"$header: $value\"");
	}
}
$rq .= "\r\n";

fwrite($fs, $rq);


$fsi = fopen("php://input", "rb");

$rq_written_body = 0;
while (!(feof($fsi))) {
	$rqdata = fread($fsi, 1024);
	$rq_written_body += strlen($rqdata);
	lg("rq writing body len: $rq_written_body");
	fwrite($fs, $datai);
}


$httpline = fgets($fs);
lg("rs writing httpline: \"$httpline\"");
header($httpline);

while (($sread = fgets($fs)) != "\r\n") {
	lg("rs writing header:   \"$sread\"");
	header($sread);
}

lg("rs writing header:   \"X-forwarded: true\"");
header("X-forwarded: true");

$rs_written_body=0;
while (!feof($fs)) {
	$rsdata = fread($fs, 1024);
	$rs_written_body += strlen($rsdata);
	#lg("rs written body:     \"$rs_written_body\"");
	echo $rsdata;
}
lg("rs donewrt body: $rs_written_body");