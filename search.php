//Скрипт быстрого поиска через Ajax-запрос
//Здесь опущено подключение к бд, настройки, объявление глоабльных переменных и т.д.

setlocale(LC_ALL, 'ru_RU.CP1251');

function _deletePercentSymbol( $str )	{
	$str = str_replace( "%", "", $str );
	return $str;
}

function LatinToRu( $str ) {
	$b = 'QWERTYUIOP{}ASDFGHJKL:ZXCVBNM<>qwertyuiop[]asdfghjkl;"zxcvbnm,.';
	$a = 'ЙЦУКЕНГШЩЗХЪФЫВАПРОЛДЖЯЧСМИТЬБЮйцукенгшщзхъфывапролджэячсмитьбю';
	$str = strtr($str,$a, $b);
	$str = strtr($str,$b, $a);
	$str = str_replace("'", 'э', $str);
	return $str;
}

if (isset($_GET["search"]))
{
  //Если написали не на русской раскладке - переводим
	if(!preg_match("([a-zA-Z]+)", $_GET["search"], $_match)){
		$_GET["search"] = iconv("UTF-8","windows-1251",$_GET["search"]);
	}else {
		$_GET["search"] = LatinToRu($_GET["search"]);
	}
  //Для использования в запрос к БД, экранируем и очищаем
	$_GET["search"] = TransformStringToDataBase( $_GET["search"] );
	$_GET["search"] = _deletePercentSymbol( $_GET["search"] );
  
  //Если поиск по коду товара (он числовой)
	if(is_numeric($_GET["search"])){
		$query = db_query("SELECT name, Price, productID FROM ".PRODUCTS_TABLE." WHERE product_code = '".$_GET["search"]."' and enabled = 1") or die(db_error());
	}
  //Иначе ищем по названию и коду
	else {
		$query = db_query("SELECT name, Price, productID FROM ".PRODUCTS_TABLE." WHERE ((LOWER(name) LIKE '%".strtolower($_GET["search"])."%') or (product_code = '".$_GET["search"]."')) and enabled = 1 limit 0, 10") or die(db_error());
	}
  //Возвращаем JSON
	header ( 'Content-Type: text/json; charset=UTF-8' );
	$res = array();
	while($row = db_fetch_row($query))	{
		$res[] = array(
			'id' => $row['productID'],
			'label' => iconv ( "windows-1251", "UTF-8", $row['name']),
			'value' => iconv ( "windows-1251", "UTF-8", $row['name'])
		);
	}
	echo (json_encode($res));
	exit (); //Завершаем вывод.
}
