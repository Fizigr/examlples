<?php

/**
 *
 * @author Половников C. А., aka Fizigr
 * @version 1.0
 * date: 07-07-2013
 *        
 */
class realty {
	
	function __construct() {
	}
	
	public static function getAllRealty($id = null, $type_id = null, $CurrentPage = null, $PerPage = null, $wh = "", $order = "", $priceFormat = null) {
		$PerPage = isset($PerPage)?$PerPage:mysql::getCount(T_REALTY);
		$CurrentPage = isset($CurrentPage)?$CurrentPage:1;
		$order = ($order == "")?$order:$order.", ";
		if(isset($type_id)) {
			if($wh != "")
				$wh .= " and type_id = '".$type_id."'";
			else
				$wh = "type_id = '".$type_id."'";
		}
		if(!isset($id)) {
			if($wh != "")
				$wh = " where ".$wh;
			$query = "select"."
					"."	id, end_date, DATE_FORMAT(end_date,'%d.%m.%Y') as Edata, add_date, DATE_FORMAT(add_date,'%d.%m.%Y') as Adata,"."
					"." price, adress, description, name, url, type_id, publish"."
					"." from ".T_REALTY.$wh." order by ".$order."add_date DESC"."
					"." limit ".(($CurrentPage-1)*$PerPage).", ".$PerPage."";
		}
		else {
			if($wh != "")
				$wh .= " and id='".$id."'";
			else
				$wh = "id='".$id."'";
			if($wh != "")
				$wh = " where ".$wh;
			$query = "select"."
					"."	id, end_date, DATE_FORMAT(end_date,'%d.%m.%Y') as Edata, add_date, DATE_FORMAT(add_date,'%d.%m.%Y') as Adata,"."
					"." price, adress, description, name, url, type_id, publish"."
					"." from ".T_REALTY.$wh." order by ".$order."add_date";
		}
		$q = mysql::query($query);
		$res = array();
		while ($row = mysql::db_fetch_row($q)) {
			$row["url"] = defence::StripSlashesGPC($row["url"]);
			$row["sources"] = self::getAllSourceByRealty($row["id"], $row["type_id"]);
			$row["photo_cnt"] = mysql::getCount(T_PHOTO, "realty_id = '".$row["id"]."'");
			if ($priceFormat != null) {
				$row["price"] = number_format( $row["price"], 0, '.', ' ' ); 
			}
			$f = mysql::query("select id, realty_id, name, photo, add_date, DATE_FORMAT(add_date,'%Y') as year from ".T_PHOTO." where realty_id = '".$row["id"]."'");
			$photo = array();
			while($r = mysql::db_fetch_row($f)) {
				$photo[] = $r;
			}
			$row["photo"] = $photo;
			$res[] = $row;
		}
		return $res;
	}
	public static function getAllSourceByRealty($id, $type_id = "", $full = null) {
		if($type_id == "") {
			$type_id = self::getReqById("type_id", T_REALTY, $id);
			$type_id = $type_id["type_id"];
		}
		$q = mysql::query("select t1.source_id from ".T_SOURCE_TYPE." as t1
				left join ".T_SOURCEBOOK." as t2
				on (t1.source_id = t2.id)
				where type_id = '".$type_id."'
				order by t2.name ASC");
		$res = array();
		$i=0;
		while($row = mysql::db_fetch_row($q)) {
			$sources = mysql::query("select id, name, view from ".T_SOURCEBOOK." where id = '".$row["source_id"]."'");
			while ($row1 = mysql::db_fetch_row($sources)) {
				$res[$i]["source_id"] = $row1["id"];
				$res[$i]["source_name"] = $row1["name"];
				$res[$i]["source_view"] = $row1["view"];
				$details = mysql::query("select id, name from ".T_SOURCE_DETAILS." where source_id = '".$row1["id"]."'");
				$j=0; $det = array();
				while ($row2 = mysql::db_fetch_row($details)) {
					$det[$j]["id"] = $row2["id"];
					$det[$j]["name"] = $row2["name"];
					$datailsVal = mysql::query("select value from ".T_SOURCE_VALUES." where source_id = '".$row1["id"]."' and realty_id='".$id."' and detail_id='".$row2["id"]."'");
					$row3 = mysql::db_fetch_row($datailsVal);
					$det[$j]["val"] = $row3["value"];
					$res[$i]["details"] = $det;
					$j++;
				}
			}
			$i++;
		}
		return $res;
	}
	public function checkDateOnOff() {
		$now = core::get_current_time();
		$charset = "Content-Type: text/html; charset=windows-1251";
		$text2mail = '
			<!DOCTYPE HTML>
			<html>
				<head>
				<title>Некоторые объявления отключены</title>
				<meta content="text/html; charset=windows-1251" http-equiv="Content-Type" />
				<link href="'.core::$settings["siteurl"].'css/main.css" rel="stylesheet" type="text/css" />
				</head>
				<body style="background-color: #c5c5c5; padding: 10px 10px 10px 10px;">
				<div align="center" style="padding: 20px; background-color: #ffffff">
		';
		$text2mail .= '<p>Здравствуйте!</p>';
		$text2mail .= '<p>Некоторые объявления были автоматически отключены, так как дата отключения наступила.</p>';
		$text2mail .= '<p>При нажатии на ссылку, Вы попадете в панель управления, где сможете продлить или удалить объявление. Вы должны быть авторизованы на сайте.</p>';
		$i = 0;
		$id = array();
		$q = mysql::query("select id, name from ".T_REALTY." where end_date<='".$now."' and publish = 1 order by add_date desc");
		while ($row = mysql::db_fetch_row($q)) {
			$text2mail .= '<p><a href="'.core::$settings["siteurl"].'admin.php?dpt=realty&sub=adverts&id='.$row["id"].'" title="Редактировать" target="_blank">'.$row["name"].'</a></p>';
			$id[$i++] = $row["id"];
		}
		$text2mail .= '<br><p style="text-align: right;">С уважением, <a href="'.core::$settings["siteurl"].'" title="Перейти на сайт">'.core::$settings["sitename"].'</a></p>';
		$text2mail .= '
				</div>
				<p>
				&nbsp;</p>
				</body>
			</html>
		';
		//die($text2mail);
		foreach ($id as $key=>$val) 
			mysql::update(T_REALTY, array("publish" => "0"), "id = '".$val."'");
		if ($i!=0) {
			mail(core::$settings["main_email"],	"Сняты с публикации объявления на сайте ".core::$settings["sitename"],
			$text2mail,	"From: \"".core::$settings["sitename"]."\"<".core::$settings["main_email"].">\n".$charset."\nReturn-path: <".core::$settings["main_email"].">" );
		}
	}
	public static function modRealty($data, $edit = NULL) {
		global $smarty;
		$message = "";
		//if (isset($data["publish"]) && $data["publish"] == "on") $data["publish"] = "1"; else $data["publish"] = "0";
	
		if ($data["name"] == "")
			$message = "<font color='red'>Наименование должно быть заполнено.</font>";
			
		if ($message == "") {
			if (isset($data["details"]))
				$details = $data["details"];
			else 
				$details = "";
			if (isset($data["detail_val"]))
				$detail_val = $data["detail_val"];
			else
				$detail_val = "";
			$detail_val = $data["detail_val"];
			unset($data["detail_val"]);
			unset($data["details"]);
			$data["url"] = core::convert2Lat($data["name"]);
			if (isset($edit))
				$data["url"] = str_replace( "'", "\'", $data["url"]);
			$data["url"] = defence::stripTags($data["url"]);
			$data["name"] = defence::stripTags($data["name"]);
			$count = mysql::db_fetch_row(mysql::query("SELECT id, count(id) FROM ".T_REALTY." where url='".$data["url"]."' limit 1"));
			if ($count[1] > 0) {
				if (isset($edit)) {
					if($edit != $count[0])
						$data["url"] = $data["url"]."-".$edit; //Свой ID, который уже есть
				}
				else {
					$last_id = mysql::db_fetch_row(mysql::query("SELECT max(id) FROM ".T_REALTY.""));
					$data["url"] = $data["url"]."-".($last_id[0]+1); //ID свой, но который будет после insert'a
				}
			}
			//Добавляем буквы в начало УРЛ, если УРЛ содержит одни цифры
			if(preg_match("/^([0-9]+)$/i", $data["url"])) $data["url"] = "content-".$data["url"];
			$url = $data["url"];
			unset($data["url"]);
			$data["url"] = $url;
			if(isset($edit)) {
				//unset($data["add_date"]);
				$url = $data["url"];
				unset($data["url"]);
				$data["url"] = $url;
				mysql::update(T_REALTY, $data, "id = '".$edit."'");
				$message = "<font color='green'>Объявление успешно обновлено.</font>";
				$lastID = $edit;
			} else {
				
				if ($data["add_date"] == "")
					$data["add_date"] = core::get_current_time();
				$data["end_date"] = $data["add_date"] + 30;
				mysql::insert(T_REALTY, $data);
				$lastID = mysql::$lastId;
				$message = "<font color='green'>Объявление успешно добавлено.</font>";
			}
			//Работаем со справочниками, их реквизитами и значениями
			if($details != "") {
				foreach($details as $key => $val) {
					mysql::query("delete from ".T_SOURCE_VALUES." where realty_id = '".$lastID."' and source_id = '".$key."'");
					if ($val != 0)
						mysql::insert(T_SOURCE_VALUES, array('realty_id' => $lastID, 'source_id' => $key, 'detail_id' => $val, 'value' => 'yes'));
				}
			}
			if($detail_val != "") {
				$i = 0;
				foreach($detail_val as $key => $val) {
					$ids = preg_split('/_/', $key, -1, PREG_SPLIT_OFFSET_CAPTURE);
					if ($i == 0)
						mysql::query("delete from ".T_SOURCE_VALUES." where realty_id = '".$lastID."' and source_id = '".$ids[0][0]."'");
					if ($val != "")
						mysql::insert(T_SOURCE_VALUES, array('realty_id' => $lastID, 'source_id' => $ids[0][0], 'detail_id' => $ids[1][0], 'value' => $val));
					$i++;
				}
			}
		} else {
			//Все POST переменные загоняем в smarty для вывода в форму
			foreach ($data as $key => $val) {
				$smarty->assign($key, $val);
			}
		}
		return $message;
	}
	public static function getReqInTable($req, $table, $order= "", $wh = "") {
		if ($order != "")
			$order = "order by ".$order;
		if ($wh != "")
			$wh = "where ".$wh;
		$q = mysql::query("select ".$req." from ".$table." ".$wh." ".$order."");
		$res = array();
		while ($row = mysql::db_fetch_row($q))
			$res[] = $row;
		return $res;
	}
	public static function getReqById($req, $table, $id) {
		$res = mysql::db_fetch_row(mysql::query("select ".$req." from ".$table." where id='".$id."'"));
		return $res;
	}
	public function newRealty($data) {
		mysql::insert(T_REALTY, $data);
		$message = "<font color=\"green\">Объявление создано с присвоением типа недвижимости</font>";
		return $message;
	}
	public function delRealty($id) {
		$type_id = self::getReqById("type_id", T_REALTY, $id);
		//Удаляем значения справочников
		$q = mysql::query("delete t1.* from ".T_SOURCE_VALUES." as t1
							left join ".T_REALTY." as t2
							on(t1.realty_id = t2.id)
							left join ".T_SOURCE_TYPE." as t3
							on(t1.source_id = t3.source_id and t2.type_id = t3.type_id)
							where t1.realty_id = '".$id."' and t3.type_id = '".$type_id["type_id"]."' ");
		//Удаляем объявление
		mysql::query("delete from ".T_REALTY." where id = '".$id."'");
		//Удаление изображений
		$y = mysql::query("select photo, DATE_FORMAT(add_date,'%Y') as year from ".T_PHOTO." where realty_id = '".$id."'");
		while($row = mysql::db_fetch_row($y)) {
			if (isset($row["photo"])) {
				unlink($_SERVER['DOCUMENT_ROOT']."/images/photoalbum/".$row["year"]."/".$row["photo"]);
				unlink($_SERVER['DOCUMENT_ROOT']."/images/photoalbum/".$row["year"]."/thumbs"."/".$row["photo"]);
				unlink($_SERVER['DOCUMENT_ROOT']."/images/photoalbum/".$row["year"]."/thumbs_normal"."/".$row["photo"]);
			}
		}
		mysql::query("DELETE FROM ".T_PHOTO." WHERE realty_id = '".$id."'");
		$message = "<font color=\"green\">Объявление удалено</font>";
		return $message;
	}
	
	function __destruct() {
	}
	public function getSourcesByType($type_id) {
		$res = array();
		$i = 0;
		$q = mysql::query(
			"select t1.id as source_id, t1.name as source_name, t1.view as source_view
			from ".T_SOURCEBOOK." as t1
			left join ".T_SOURCE_TYPE." as t2
			on (t1.id = t2.source_id)
			where t2.type_id = ".$type_id." order by t1.view desc"
		);
		while ($row1 = mysql::db_fetch_row($q)) {
			$res[$i]["source_id"] = $row1["source_id"];
			$res[$i]["source_name"] = $row1["source_name"];
			$res[$i]["source_view"] = $row1["source_view"];
			$details = mysql::query("select id, name from ".T_SOURCE_DETAILS." where source_id = '".$row1["source_id"]."'");
			$j=0; $det = array();
			while ($row2 = mysql::db_fetch_row($details)) {
				$det[$j]["id"] = $row2["id"];
				$det[$j]["name"] = $row2["name"];
				$datailsVal = mysql::query("select value from ".T_SOURCE_VALUES." where source_id = '".$row1["source_id"]."' and detail_id='".$row2["id"]."'");
				$row3 = mysql::db_fetch_row($datailsVal);
				$det[$j]["val"] = $row3["value"];
				$res[$i]["details"] = $det;
				$j++;
			}
			$i++;
		}
		
		return $res;
	}
	public function searchRealty($data, $CurrentPage = null, $PerPage = null, $order = "", $priceFormat = null) {
		$PerPage = isset($PerPage)?$PerPage:mysql::getCount(T_REALTY);
		$CurrentPage = isset($CurrentPage)?$CurrentPage:1;
		$order = ($order == "")?$order:$order.", ";
		if (isset($data["SIMPLE"]) && !isset($data["ADV"])) {
			$wh = "publish = 1";
			if ($data["SIMPLE"]["realty_type"] != "0") {
				$wh .= " and type_id = '".(int)$data["SIMPLE"]["realty_type"]."'";
			}
			if ($data["SIMPLE"]["price"] != "") {
					$wh .= " and price <= '".$data["SIMPLE"]["price"]."'";
			}
			return self::getAllRealty(null, null, $CurrentPage, $PerPage, $wh, "add_date desc", $priceFormat);
		}
		elseif(isset($data["ADV"])) {
			$wh = "";
			$group = "";
			if ($data["SIMPLE"]["realty_type"] != "0") {
				$wh = "t1.type_id = '".(int)$data["SIMPLE"]["realty_type"]."'";
			}
			if ($data["SIMPLE"]["price"] != "") {
				$wh .= " and t1.price <= '".$data["SIMPLE"]["price"]."'";
			}
			foreach ($data["ADV"] as $key => $val) {
				if ($key == "detail_id") {
					$i = 0;
					foreach ($val as $id => $value) {
						if ($value != 0) {
							$i++;
						}
					}
					if ($i > 1) {
						$j = 0;
						foreach ($val as $id => $value) {
							if ($j == 0)
								$wh .= " and ((t2.detail_id = '".$value."' and t2.source_id = '".$id."')";
							else
								$wh .= " or (t2.detail_id = '".$value."' and t2.source_id = '".$id."')";
							$j++;
						}
						$group = " group by t2.source_id ";
						$wh .= ")";
					}
					elseif ($i == 1) {
						foreach ($val as $id => $value) {
							if ($value != 0) {
								$wh .= " and (t2.detail_id = '".$value."' and t2.source_id = '".$id."')";
							}
						}
					}
				}
				//Поиск площади в будущем...
				/*
				elseif($key == "detail_val") {
					foreach ($val as $id => $value) {
						if ($value != "") {
							$wh .= " and (t2.detail_id = '".$id."' and t2.value<='".$value."')";
						}
					}
				}
				*/
			}
			$wh .= " and t1.publish = 1"; 
			if($wh != "")
				$wh = " where ".$wh;
			$query = "select DISTINCT "."
				"."	t1.id as realty_id, DATE_FORMAT(t1.add_date,'%d.%m.%Y') as Adata,"."
				"." t1.price, t1.description, t1.name as realty_name, t1.url, t1.type_id as realty_type_id"."
				"." from ".T_REALTY." as t1"."
				"." left join ".T_SOURCE_VALUES." as t2"."
				"." on (t2.realty_id = t1.id)"."
				"." left join ".T_SOURCE_TYPE." as t3"."
				"." on (t3.source_id = t2.source_id and t3.type_id = t1.type_id)"."
				".$wh.$group." order by ".$order."add_date DESC"."
				"." limit ".(($CurrentPage-1)*$PerPage).", ".$PerPage."";
			$res = array();
			$i = 0;
			$q = mysql::query($query);
			while($row = mysql::db_fetch_row($q)) {
				$row["name"] = $row["realty_name"];
				$row["photo_cnt"] = mysql::getCount(T_PHOTO, "realty_id = '".$row["realty_id"]."'");
				if ($priceFormat != null) {
					$row["price"] = number_format( $row["price"], 0, '.', ' ' );
				}
				$f = mysql::query("select id, realty_id, name, photo, add_date, DATE_FORMAT(add_date,'%Y') as year from ".T_PHOTO." where realty_id = '".$row["realty_id"]."'");
				$photo = array();
				while($r = mysql::db_fetch_row($f)) {
					$photo[] = $r;
				}
				$row["photo"] = $photo;
				$res[$i++] = $row;
			}
			return $res;
		}

	}
	public function searchRealtyCnt($data) {
		if (isset($data["SIMPLE"]) && !isset($data["ADV"])) {
			$wh = "";
			if ($data["SIMPLE"]["realty_type"] != "0") {
				$wh = "type_id = '".(int)$data["SIMPLE"]["realty_type"]."'";
			}
			if ($data["SIMPLE"]["price"] != "") {
				if ($wh != "")
					$wh .= " and price <= '".$data["SIMPLE"]["price"]."'";
				else
					$wh = "price <= '".$data["SIMPLE"]["price"]."'";
			}
			if ($wh != "")
				$wh = " where ".$wh;
			$q = mysql::query("select count(id) from ".T_REALTY.$wh);
			$cnt = mysql::db_fetch_row($q);
			return $cnt[0];
		}
		elseif(isset($data["ADV"])) {
			$wh = "";
			$group = "";
			if ($data["SIMPLE"]["realty_type"] != "0") {
				$wh = "t1.type_id = '".(int)$data["SIMPLE"]["realty_type"]."'";
			}
			if ($data["SIMPLE"]["price"] != "") {
				$wh .= " and t1.price <= '".$data["SIMPLE"]["price"]."'";
			}
			foreach ($data["ADV"] as $key => $val) {
				if ($key == "detail_id") {
					$i = 0;
					foreach ($val as $id => $value) {
						if ($value != 0) {
							$i++;
						}
					}
					if ($i > 1) {
						$j = 0;
						foreach ($val as $id => $value) {
							if ($j == 0)
								$wh .= " and ((t2.detail_id = '".$value."' and t2.source_id = '".$id."')";
							else
								$wh .= " or (t2.detail_id = '".$value."' and t2.source_id = '".$id."')";
							$j++;
						}
						$group = " group by t2.source_id ";
						$wh .= ")";
					}
					elseif ($i == 1) {
						foreach ($val as $id => $value) {
							if ($value != 0) {
								$wh .= " and (t2.detail_id = '".$value."' and t2.source_id = '".$id."')";
							}
						}
					}
				}
				//Поиск площади в будущем...
				/*
					elseif($key == "detail_val") {
				foreach ($val as $id => $value) {
				if ($value != "") {
				$wh .= " and (t2.detail_id = '".$id."' and t2.value<='".$value."')";
				}
				}
				}
				*/
			}
			$wh .= " and t1.publish = 1";
			if($wh != "")
				$wh = " where ".$wh;
			$query = "select DISTINCT "."
				"."	t1.id as realty_id
				"." from ".T_REALTY." as t1"."
				"." left join ".T_SOURCE_VALUES." as t2"."
				"." on (t2.realty_id = t1.id)"."
				"." left join ".T_SOURCE_TYPE." as t3"."
				"." on (t3.source_id = t2.source_id and t3.type_id = t1.type_id)"."
				".$wh.$group;
			$res = array();
			$i = 0;
			$q = mysql::query($query);
			while(mysql::db_fetch_row($q)) {
				$i++;
			}
			return $i;
		}
	
	}
}

?>
