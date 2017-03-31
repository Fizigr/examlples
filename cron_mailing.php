<?php
//Скрипт для запуска по крону. Можно запускать каждую секунду или один раз в 30 сек для небольших баз. Около 1000 адресов.
//Подключение к бд опущено.

function getHeaders () {
	$headers = "";
	$headers .= "Content-Type: text/html; charset=windows-1251\r\n";
	$headers .= "From: ФИО <test@test.te>\r\n";
	$headers .= "Date: ".date("d.m.Y (H:i:s)", time())."\r\n";
	$headers .= "Reply-To: ФИО <test1@test.te>\r\n";
	$headers .= "X-Mailer: PHP/".phpversion()."\r\n";
	return $headers;
}
function GetHTML($tpl, $data) {
	$tpl = str_replace('{$fname}', $data["fname"], $tpl);
	//Проверяем unsubscribe hash, если есть, формируем ссылку, если нет, формируем hash и записываем его в бд и формируем ссылку :))
	$hash = ($data["md5_unsubscribe"] != '') ? $data["md5_unsubscribe"] : md5($data["email"].'-and-'.$data["subscriberID"]);
	if($data["md5_unsubscribe"] == '') {
		db_query("UPDATE ss_mailing_list SET md5_unsubscribe = '".$hash."' WHERE id ='".$data["subscriberID"]."'");
	}
	$unsLink = 'http://test.te/unsubscribe/'.$hash;
	$tpl = str_replace('{$unsLink}', $unsLink, $tpl);

	return $tpl;
}

$q = db_query("select id, title, template from ss_mailing where switch_on = '1'") or die(db_error());
while ($row = db_fetch_row($q)) {
	$q1 = db_query("select subscriberID from ss_mailing_logs where mailingID = '".$row["id"]."' and date_send = '0000-00-00 00:00:00' limit 1") or die(db_error());
	while ($row1 = db_fetch_row($q1)) {
		$nameq = db_query("select email, fname, md5_unsubscribe from ss_mailing_list where id = '".$row1["subscriberID"]."' and subscribe = '1'") or die(db_error());
		$namerow = db_fetch_row($nameq);
		if ($namerow["fname"] != "" && $namerow["email"] != "") {
			$data = array();
			$data["fname"] = $namerow["fname"];
			$data["email"] = $namerow["email"];
			$data["subscriberID"] = $row1["subscriberID"];
			$data["md5_unsubscribe"] = $namerow["md5_unsubscribe"];

			if (mail($namerow["email"], $row["title"], GetHTML($row["template"], $data), getHeaders())) {
        /* для отладки
          $fp = fopen('mailing_log.txt','a');
          $startdate = "Script start time: ".date("d.m.Y, G:i:s")."\n";
          fputs($fp, $startdate);
          fputs($fp, $namerow["email"]." - mailed\n");
          $enddate = "Script finish time: ".date("d.m.Y, G:i:s")."\n";
          fputs($fp, $enddate);
          fclose($fp);
        */
				db_query("update ss_mailing_logs set date_send = '".date("Y-m-d H:i:s")."' where subscriberID = '".$row1["subscriberID"]."'") or die(db_error());
				die(); // За 1 запуск - 1 отправление. Можно было в запросе использовать limit
			}
		}
	}
}

?>
