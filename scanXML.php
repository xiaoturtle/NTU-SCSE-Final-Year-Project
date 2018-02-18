<?php
$myReceivedArr = json_decode($_POST['myArray']);

$diagram = new SimpleXMLElement($myReceivedArr[2]);
$root = $diagram->children();
$legalItemList = array();
$found= false;
foreach($root->children() as $child) {
    $styleElements = explode(";", $child['style']);
    foreach($styleElements as $value) {
        if (strpos($value, 'id=') !== false) {
            if (strpos($value, 'generalization') !== false) {
               // echo "generalization";
            }else if (strpos($value, 'provideInterface') !== false) {
                //echo "provideInterface";
            }else if (strpos($value, 'port') !== false) {
                //echo "port";
            }else if(strpos($value, 'requireInterface') !== false) {
                //echo "requireInterface";
            }else if(strpos($value, 'component') !== false) {
               // echo "component";
                $id = $child["id"];
                if(strcmp($child['value'],"&laquo;Annotation&raquo;<br/><b>Component</b>")==0){
                $child['value'] =(string)"&laquo;Annotation&raquo;<br/><b>Component ".$id."</b>";
                $found = true;
                }
            }else if(strpos($value, 'parentComponent') !== false) {
               // echo "parentComponent";
               $id = $child["id"];
               if(strcmp($child['value'],"&laquo;Annotation&raquo;<br/><b>Component</b>")==0){
                $child['value'] =(string)"&laquo;Annotation&raquo;<br/><b>Component ".$id."</b>";
                $found = true;
               }
               //$child['value'] =(string)"&laquo;Annotation&raquo;<br/><b>Component ".$id."</b>";
               //$child['value']=str_replace("Component", "Component".$id, $child['value']);
            }else if(strpos($value, 'cShape') !== false) {
                //echo "cShape";
            }else if(strpos($value, 'dependency') !== false) {
                //echo "dependency";
            }else if(strpos($value, 'text') !== false) {
               // echo "text";
            }else{     
                //illegal item with wrong id
                $error = array($child["id"],"Cannot identify object with ID: ".$child["id"]);
            }
        }
    }
}
if($found){
    echo $diagram->asXML();
}else{
    echo "nil";
}

?>