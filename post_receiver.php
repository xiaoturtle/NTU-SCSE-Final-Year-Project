<?php
/*$arr_variable = array($_POST,'23454');
$data['result'] = $arr_variable;
$data2['myXML'] = array($_POST);
echo json_encode($data);
*/

//read xml from the ajax post

$myReceivedArr = json_decode($_POST['myArray']);
//echo $myReceivedArr[2];

$diagram = new SimpleXMLElement($myReceivedArr[2]);
//echo $myReceivedArr[2];
$root = $diagram->children();
$legalItemList = array();
$legalBottomItemList = array();
$componentList = array();
$PRList = array();
$illegalItemList = array();
$resultsArr = array();
$componentValueArray = array();
$layer1Items = array();
$layer1BottomItems = array();
      /*  foreach($child as $key => $value) {
        /*
        if($role == "Language")
        echo("[".$key ."] ".$value . "<br />");
        echo 
        
    }*/
   // $role = $child->attributes();
foreach($root->children() as $child) {
    $styleElements = explode(";", $child['style']);
    foreach($styleElements as $value) {
        if (strpos($value, 'id=') !== false) {
            if (strpos($value, 'generalization') !== false) {
               // echo "generalization";
                $item = array("generalization",$child);
                array_push($legalItemList,$item);
                if (strpos($child['parent'], '1') !== false) {
                    array_push($layer1Items,$item);
                }
            }else if (strpos($value, 'provideInterface') !== false) {
                //echo "provideInterface";
                $item = array("provideInterface",$child);
                array_push($legalItemList,$item);
                if (strpos($child['parent'], '1') !== false) {
                    array_push($layer1Items,$item);
                }
            }else if (strpos($value, 'port') !== false) {
                //echo "port";
                $item = array("port",$child);
                array_push($legalItemList,$item);
                if (strpos($child['parent'], '1') !== false) {
                    array_push($layer1Items,$item);
                }
            }else if(strpos($value, 'requireInterface') !== false) {
                //echo "requireInterface";
                $item = array("requireInterface",$child);
                array_push($legalItemList,$item);
                if (strpos($child['parent'], '1') !== false) {
                    array_push($layer1Items,$item);
                }
            }else if(strpos($value, 'component') !== false) {
               // echo "component";
                $item = array("component",$child);
                array_push($legalBottomItemList,$item);
                array_push($componentValueArray, $child["value"]);
                if (strpos($child['parent'], '1') !== false) {
                    array_push($layer1BottomItems,$item);
                }
            }else if(strpos($value, 'parentComponent') !== false) {
               // echo "parentComponent";
                $item = array("parentComponent",$child);
                array_push($legalBottomItemList,$item);
                array_push($componentValueArray, $child["value"]);
                if (strpos($child['parent'], '1') !== false) {
                    array_push($layer1BottomItems,$item);
                }
            }else if(strpos($value, 'cShape') !== false) {
                //echo "cShape";
                $item = array("cShape",$child);
                //dont have to push as it belongs to the parentComponent
                //array_push($legalItemList,$item);
                if (strpos($child['parent'], '1') !== false) {
                    array_push($layer1Items,$item);
                }
            }else if(strpos($value, 'dependency') !== false) {
                //echo "dependency";
                $item = array("dependency",$child);
                array_push($legalItemList,$item);
                if (strpos($child['parent'], '1') !== false) {
                    array_push($layer1Items,$item);
                }
            }else if(strpos($value, 'text') !== false) {
               // echo "text";
                $item = array("text",$child);
                array_push($legalItemList,$item);
                if (strpos($child['parent'], '1') !== false) {
                    array_push($layer1Items,$item);
                }
            }else{     
                //illegal item with wrong id
                $error = array($child["id"],"Cannot identify object with ID: ".$child["id"]);
                array_push($resultsArr,$error);
            }
        }
    }
}
$layer1Items = array_merge($layer1Items, $layer1BottomItems);
$legalItemList = array_merge($legalItemList, $legalBottomItemList);
//after sorting diagram components into their own types, perform checking on individual item

//Varname check for all component diagrams (parent and child)
foreach($legalItemList as $item){
    if(strcmp($item[0],"parentComponent")==0 || strcmp($item[0],"component")==0){
        //check for value
        $xmlNode = $item[1];
        $itemValue= strtolower($xmlNode["value"]);
        //correct forms of annotation
        $query1 ="«annotation»";
        $query2 ="&laquo;annotation&raquo;";
        $query3 ="«component»";
        $query4 ="&laquo;component&raquo;";
        $query5 ="<<component>>";
        $replace = "";
        $componentName="-";
        if(empty($itemValue)==false){
            if(substr($itemValue, 0, strlen($query1)) === $query1){
                $componentName = str_replace($query1, $replace, $itemValue);
                $componentName = trimName($componentName);
            }else if(substr($itemValue, 0, strlen($query2)) === $query2){
                $componentName = str_replace($query2, $replace, $itemValue);
                $componentName = trimName($componentName);
            }else if( substr($itemValue, 0, strlen($query3)) === $query3){
                $componentName = str_replace($query3, $replace, $itemValue);
                $componentName = trimName($componentName);
            }else if(substr($itemValue, 0, strlen($query4)) === $query4){
                $componentName = str_replace($query4, $replace, $itemValue);
                $componentName = trimName($componentName);
            }else if(substr($itemValue, 0, strlen($query5)) === $query5){
                $componentName = str_replace($query5, $replace, $itemValue);
                $componentName = trimName($componentName);
            }else{
                //component naming convention incorrect. add into errorlist
                //echo "Naming convention for this component is incorrect";
                $error = array($xmlNode["id"],"Naming convention for ".$item[0]." of ID: ".$xmlNode["id"]." is incorrect. It should be «component».");
                array_push($resultsArr,$error);
            }
            if($componentName!="-" && strcmp($componentName,"Component")!=0){
                // the first check passed
                // perform varname lookup to check if other components have the same name
                //echo $componentName;
                $matches = array_filter($componentValueArray, function($var) use ($componentName) { return preg_match("/\b$componentName\b/i", $var); });
                if(sizeof($matches)>1){
                    //add to error list
                   // echo "Duplicate component found";
                   $error = array($xmlNode["id"],"There is duplicate name for ".$item[0]." of ID: ".$xmlNode["id"]."-".sizeof($matches));
                   array_push($resultsArr,$error);
                }
            }
        }else{
            //component name is empty highlight error
            //echo "There is no name for this component";
            $error = array($xmlNode["id"],"There is no name for ".$item[0]." of ID: ".$xmlNode["id"].".");
            array_push($resultsArr,$error);
        }
        if(strcmp($item[0],"parentComponent")==0){
        //in each parent component, find all child component and make sure its coordinates are within the parent.
        //if not child of the parent component check if it's intersect with this parent component
            //first compute the x and y coordinates of the parentComponent
            $myChildList= array();
            $parentCompoentProperty = $item[1]->children();
            $parentID= $item[1]["id"];
            $parentStartX =(int) $parentCompoentProperty[0]["x"];
            $parentStartY = (int)$parentCompoentProperty[0]["y"];
            $parentWidth =(int) $parentCompoentProperty[0]["width"];
            $parentHeight =(int)$parentCompoentProperty[0]["height"];
            $parentEndX=(int) $parentWidth+ $parentStartX;
            $parentEndY=(int) $parentHeight+ $parentStartY;
            //check for rotation
            //retrieve the value of the parentid
            foreach($legalItemList as $child){
                $xmlNode = $child[1];
                //ignore if i am checking with myself
                if(strcmp($parentID,$xmlNode["id"])!=false){
                    $myParent = $xmlNode["parent"];
                    $childNode= $xmlNode->children();
                    $childStartX=(int)$childNode[0]["x"];
                    $childStartY=(int)$childNode[0]["y"];
                    $childWidth=(int)$childNode[0]["width"];
                    $childHeight=(int)$childNode[0]["height"];
                // $childHeight=(int)$childNode[0]["height"];
                    $childEndX=(int)$childStartX + $childWidth;
                    $childEndY=(int)$childStartY + $childHeight;
                    //check for rotation
                    if(strcmp($parentID,$myParent)==false){
                        // child is in parent
                        //echo "parent found".$parentID.";".$myParent;
                        //this applies for anything that is within the parent
                        $glocalChildStartX=(int)$childNode[0]["x"]+$parentStartX;
                        $globalchildStartY=(int)$childNode[0]["y"]+$parentStartY;
                        $childEndX=(int)$glocalChildStartX + $childWidth;
                        $childEndY=(int)$globalchildStartY + $childHeight;   
                        if(!strcmp($child[0],"port")){
                            //port found
                            $glocalChildStartX=(int)$childNode[0]["x"]+$parentStartX;
                            $globalchildStartY=(int)$childNode[0]["y"]+$parentStartY;
                            $childEndX=(int)$glocalChildStartX + $childWidth;
                            $childEndY=(int)$globalchildStartY + $childHeight;
                            $portCoordinates= array();
                            if($childEndX>$parentEndX){
                                $childEndX=-1;
                            }else{
                                $childEndX=1;
                            }
                            if($childEndY>$parentEndY){
                                $childEndY=-1;
                            }
                            else{
                                $childEndY=1;
                            }
                            if($glocalChildStartX<$parentStartX){
                                $glocalChildStartX=-1;
                            }
                            else{
                                $glocalChildStartX=1;
                            }
                            if($globalchildStartY<$parentStartY){
                                $globalchildStartY=-1;
                            }
                            else{
                                $globalchildStartY=1;
                            }
                            array_push($portCoordinates, array($glocalChildStartX,$globalchildStartY));
                            array_push($portCoordinates, array($childEndX,$globalchildStartY));
                            array_push($portCoordinates, array($glocalChildStartX,$childEndY));
                            array_push($portCoordinates, array($childEndX,$childEndY));
                            $positiveCount=0;
                            $negativeCount=0;
                            for ($i = 0; $i < 4; $i++) {
                                for($j = 0; $j < 2; $j++){
                                    if($portCoordinates[$i][$j]<=0)
                                    {
                                        $negativeCount++;
                                    }else{
                                        $positiveCount++;
                                    }
                                }
                            }                             
                            /*$error = array($item[1]["id"],"negative  ".$negativeCount." positive".$positiveCount." is not along the border of parent component ID:".$item[1]["id"]);
                            array_push($resultsArr,$error);*/
                            if(!($positiveCount==6 && $negativeCount==2)){
                                //check if the location of the port is at the right position
                                if($positiveCount==8){
                                    $error = array($item[1]["id"],"The location of ".$child[0]." child object of ID:".$xmlNode["id"]." is not along the border of parent component ID:".$item[1]["id"]);
                                    array_push($resultsArr,$error);
                                }else{
                                    $error = array($item[1]["id"],"The location of ".$child[0]." child object of ID:".$xmlNode["id"]." at the edge of parent component ID:".$item[1]["id"]);
                                    array_push($resultsArr,$error);
                                }
                            }else{
                                if(!($childWidth==$childHeight)){
                                    //cannot be too big
                                    //check is a square
                                    $error = array($item[1]["id"],"The height and width of ".$child[0]." child object of ID:".$xmlNode["id"]." must be equal");
                                    array_push($resultsArr,$error);
                                }
                            }
                        }else if(($childStartX<0 ||  $childStartY<0 || $parentWidth <$childWidth || $parentHeight<$childHeight)||($childEndX>$parentEndX)||($childEndY>$parentEndY)){
                                // echo "Child not within parent"; 
                                // add into error list
                                $error = array($item[1]["id"],"The dimensions of ".$child[0]." child object of ID:".$xmlNode["id"]." is outside the border of parent component ID:".$item[1]["id"]);
                                array_push($resultsArr,$error);
                                }
                        if(strcmp($child[0],"parentComponent")==0 || strcmp($child[0],"component")==0){
                            array_push($componentList,$child);
                            //find the ports attached to the parent component
                            //add the parent coordds to the port and give method to check
                        }else if(strcmp($child[0],"requireInterface")==false || (strcmp($child[0],"provideInterface")==false)){
                            array_push($PRList,$child);
                        }
                        array_push($myChildList,$child);
                    }else{
                        // echo "parent not found".$parentID.";".$myParent;
                        //the item is not inside parent so should be not within the bound unless the item is port
                        //check x axis is within the box if true then check y axis is within the box
                        if(!strcmp($child[0],"parentComponent")==0){
                            if(!checkOverlap($parentStartX,$parentStartY,$parentEndX,$parentEndY,$childStartX, $childStartY, $childEndX, $childEndY)){
                                // check if it is port
                                // check if port located properly
                                if(!strcmp($child[0],"port")){
                                    $error = array($child[1]["id"],"The ".$child[0]." object of ID:".$xmlNode["id"]." is not within a parent component. Please re-adjust.");
                                    array_push($resultsArr,$error);   
                                }
                                /* Dont think we need to output this error
                                else{
                                    $error = array($item[1]["id"],"The ".$child[0]." object of ID:".$xmlNode["id"]." has overlap the parent component of ID: ".$item[1]["id"]);
                                    array_push($resultsArr,$error);

                                }*/
                            } 
                        }
                    }
                }
            }
            //end of checking for parent with children
            //start checking between siblings
            foreach($myChildList as $siblingKey => $sibling){
                $siblingID = $sibling[1]["id"];
                $siblingGeo = $sibling[1]->children();
                $siblingStartX =(int) $siblingGeo[0]["x"];
                $siblingStartY = (int)$siblingGeo[0]["y"];
                $siblingWidth =(int) $siblingGeo[0]["width"];
                $siblingHeight =(int)$siblingGeo[0]["height"];
                $siblingEndX=(int) $siblingWidth+ $siblingStartX;
                $siblingEndY=(int) $siblingHeight+ $siblingStartY;
                foreach($myChildList as $otherSibling){
                    $othersiblingID = $otherSibling[1]["id"];
                    if(strcmp( $siblingID, $othersiblingID )==true){
                        $othersiblingCompoentProperty = $otherSibling[1]->children();
                        $othersiblingStartX =(int) $othersiblingCompoentProperty[0]["x"];
                        $othersiblingStartY = (int)$othersiblingCompoentProperty[0]["y"];
                        $othersiblingWidth =(int) $othersiblingCompoentProperty[0]["width"];
                        $othersiblingHeight =(int)$othersiblingCompoentProperty[0]["height"];
                        $othersiblingEndX=(int) $othersiblingWidth+ $othersiblingStartX;
                        $othersiblingEndY=(int) $othersiblingHeight+ $othersiblingStartY;
                        //this is a port connection perform another kind of check
                        $styleElements = explode(";", $sibling[1]['style']);
                        $siblingRotatedArray= array();
                        $siblingRotation = 0;
                        $siblingCoordsArray= array();
                        foreach($styleElements as $value) {
                            if (strpos($value, 'rotation=') !== false) {
                                $siblingRotation= (int) str_replace("rotation=","",$value);
                                //echo "heee".$siblingRotation;
                            }
                        }
                        /**
                         * [0] - Left Top point
                         * [1] - Right Top Point
                         * [2] - Left Bottom Point
                         * [3] - Right Bottom Point
                         * [4] - LHS Left Top Point
                         * [5] - LHS Right Top Point
                         * [6] - LHS Left Bottom Point
                         * [7] - LHS Right Bottom Point
                         * [8] - RHS Left Top Point
                         * [9] - RHS Right Top Point
                         * [10] - RHS Left Bottom Point
                         * [11] - RHS Right Bottom Point
                         */
                        $siblingCoordsArray=getRotation($siblingStartX,$siblingStartY,$siblingEndX,$siblingEndY,$siblingRotation,$siblingCoordsArray,$sibling[0]);
                        //  $resultsArr=getRotation(2,3,4,5,$siblingRotation,$resultsArr,"requireInterface");
                        $styleElements = explode(";", $otherSibling[1]['style']);
                        $otherSiblingRotation = 0;
                        $otherSiblingRotated = array();
                        $otherSiblingCoordsArray= array();
                        foreach($styleElements as $value) {
                            if (strpos($value, 'rotation=') !== false) {
                                $otherSiblingRotation= (int) str_replace("rotation=","",$value);
                            }
                        }
                        $otherSiblingCoordsArray=getRotation($othersiblingStartX,$othersiblingStartY,$othersiblingEndX,$othersiblingEndY,$otherSiblingRotation,$otherSiblingCoordsArray,$otherSibling[0]);
                        //if(!checkOverlap($siblingStartX,$siblingStartY,$siblingEndX,$siblingEndY,$othersiblingStartX, $othersiblingStartY, $othersiblingEndX, $othersiblingEndY)){
                           /* echo "1x.".$siblingCoordsArray[0][0].".".$siblingCoordsArray[1][0].".".$siblingCoordsArray[2][0].".".$siblingCoordsArray[3][0];
                            echo "1y.".$siblingCoordsArray[0][1].".".$siblingCoordsArray[1][1].".".$siblingCoordsArray[2][1].".".$siblingCoordsArray[3][1];
                            echo "2x.".$otherSiblingCoordsArray[0][0].".".$otherSiblingCoordsArray[1][0].".".$otherSiblingCoordsArray[2][0].".".$otherSiblingCoordsArray[3][0];
                            echo "2y.".$otherSiblingCoordsArray[0][1].".".$otherSiblingCoordsArray[1][1].".".$otherSiblingCoordsArray[2][1].".".$otherSiblingCoordsArray[3][1];*/
                            if(checkIntersect($siblingCoordsArray,$otherSiblingCoordsArray,1)){
                                if((strcmp($sibling[0],"requireInterface")==false && (strcmp($otherSibling[0],"provideInterface")==false))||(strcmp($otherSibling[0],"requireInterface")==false && (strcmp($sibling[0],"provideInterface")==false))){
                                    if(strcmp($sibling[0],"requireInterface")==false && (strcmp($otherSibling[0],"provideInterface")==false)){
                                        if(checkIntersect($siblingCoordsArray,$otherSiblingCoordsArray,2)){
                                            //Required interface and provided interface is connected.
                                           // $error = array($siblingID,"Connected Type A");
                                           // array_push($resultsArr,$error);
                                        }else{
                                            //head of required not connected with head of provided
                                            $error = array($siblingID,"Required interface of ID: ".$siblingID." connection is not connected properly with provided interface of ID: ".$othersiblingID);
                                            array_push($resultsArr,$error);
                                        }
                                    }else if(checkIntersect($siblingCoordsArray,$otherSiblingCoordsArray,3)){
                                        //Provided interface and provided interface is connected
                                        //$error = array($siblingID,"Connected Type B");
                                        //array_push($resultsArr,$error);
                                        }else{
                                            $error = array($siblingID,"Provided interface of ID: ".$siblingID." connection is not connected properly with required interface of ID: ".$othersiblingID);
                                            array_push($resultsArr,$error);
                                        }
                            }else{
                                // echo "sibling overlapp sibling"
                                // given that both connectors are not the same type
                                if(!((strcmp($sibling[0],"component")==false && (strcmp($otherSibling[0],"provideInterface")==false))||(strcmp($otherSibling[0],"requireInterface")==false && (strcmp($sibling[0],"component")==false)))){
                                    if(!((strcmp($sibling[0],"provideInterface")==false || (strcmp($sibling[0],"requireInterface")==false)))){
                                        $error = array($siblingID,"The ".$sibling[0]." object of ID:".$sibling[1]["id"]." has overlap its sibling object of ID: ".$otherSibling[1]["id"]);
                                        array_push($resultsArr,$error);
                                    }
                                }
                            }
                        }else{
                            //there are no error in the particular item in the parent component
                            /*$error = array($siblingID,$siblingCoordsArray[0][0]."-".$siblingCoordsArray[0][1]."-".$siblingCoordsArray[1][0]."-".$siblingCoordsArray[1][1]."-".$otherSiblingCoordsArray[0][0]."-". $otherSiblingCoordsArray[0][1]."-". $otherSiblingCoordsArray[1][0]."-".$otherSiblingCoordsArray[1][1]);
                            array_push($resultsArr,$error);
                            $error = array($siblingID,"Overlap no error");
                            array_push($resultsArr,$error);*/
                        }
                    }
                }//end of line 273
                unset($myChildList[$siblingKey]);
            }//finish checking the insection of silbings
            //start checking if the connections are correct within a parent component
            // PR stands for provided and required
            foreach($PRList as $PR){
                $type = 0 ;
                $myRotatedArray = array();
                $PRID = $PR[1]["id"];
                $PRGeo = $PR[1]->children();
                $PRStartX =(int) $PRGeo[0]["x"];
                $PRStartY = (int)$PRGeo[0]["y"];
                $PRWidth =(int) $PRGeo[0]["width"];
                $PRHeight =(int)$PRGeo[0]["height"];
                $PREndX=(int) $PRWidth+ $PRStartX;
                $PREndY=(int) $PRHeight+ $PRStartY;
                $styleElements = explode(";", $PR[1]['style']);
                $PRRotation = 0;
                foreach($styleElements as $value) {
                    if (strpos($value, 'rotation=') !== false) {
                        $PRRotation= (int) str_replace("rotation=","",$value);
                        //echo "heee".$siblingRotation;
                    }
                }
                if((strcmp($PR[0],"requireInterface")==false)){
                    $type = 1;
                    $myRotatedArray = getRotation($PRStartX,$PRStartY,$PREndX,$PREndY,$PRRotation,$myRotatedArray,"requireInterface");
                }else if((strcmp($PR[0],"provideInterface")==false)){
                    $type = 2;
                    $myRotatedArray = getRotation($PRStartX,$PRStartY,$PREndX,$PREndY,$PRRotation,$myRotatedArray,"provideInterface");
                }
                $found = 0;
                foreach($componentList as $component){
                    $componentID = $component[1]["id"];
                    $componentGeo = $component[1]->children();
                    $componentStartX =(int) $componentGeo[0]["x"];
                    $componentStartY = (int)$componentGeo[0]["y"];
                    $componentWidth =(int) $componentGeo[0]["width"];
                    $componentHeight =(int)$componentGeo[0]["height"];
                    $componentEndX=(int) $componentWidth+ $componentStartX;
                    $componentEndY=(int) $componentHeight+ $componentStartY;

                    //calculate innerbox if the connection intersects the innerbox there is an error
                    $tempComponentStartX = $componentStartX + 8 ;
                    $tempComponentStartY = $componentStartY + 8;
                    $tempComponentEndX = $componentEndX - 8 ;
                    $tempComponentEndY = $componentEndY - 8;
                    
                    //check for port if port cannot be the same parent
                    $styleElements = explode(";", $component[1]['style']);
                    $componentRotation = 0;
                    foreach($styleElements as $value) {
                        if (strpos($value, 'rotation=') !== false) {
                            $componentRotation= (int) str_replace("rotation=","",$value);
                            //echo "heee".$siblingRotation;
                        }
                    }
                    $componentFinalCoordinates = array();
                    $tempComponentFinalCoordinates = array();
                    $componentFinalCoordinates = getRotation($componentStartX,$componentStartY, $componentEndX, $componentEndY, $componentRotation, $componentFinalCoordinates ,"component");
                    $tempComponentFinalCoordinates = getRotation($tempComponentStartX,$tempComponentStartY, $tempComponentEndX, $tempComponentEndY, $componentRotation, $tempComponentFinalCoordinates ,"component");
                    if($type ==1){
                        //check for require interface
                        if(checkIntersect($componentFinalCoordinates,$myRotatedArray, 4)){
                            //there is overlapp
                            //do nothing
                            if(!checkIntersect($tempComponentFinalCoordinates,$myRotatedArray, 1)){
                                $found = $componentID;
                            }else{
                                $found = -1;
                            }
                        }else {
                            //echo "Connected";
                        }
                    }else if($type==2){
                        //check for provide interface
                        if(checkIntersect($componentFinalCoordinates,$myRotatedArray, 5)){
                         //there is overlapp
                        //doing nothing
                            if(!checkIntersect($tempComponentFinalCoordinates,$myRotatedArray, 1)){
                                $found = $componentID;
                            }else{
                                $found = -1;
                            }
                        }
                    }
                }
                if($found==0){
                    $error = array($PRID  ,"The ".$PR[0]." object of ID: ".$PRID." is not connected to any component or port of a parent component.");
                    array_push($resultsArr,$error);
                }else{
                    if($found<0){
                        $error = array($siblingID,"The ".$PR[0]." object of ID: " .$PRID. " has overlap its component object of ID: ".$componentID);
                        array_push($resultsArr,$error);
                    }else{
                        //$error = array($PRID  ,$PR[0]." object of ID: ".$PRID." is connected to component ID :".$found);
                        //array_push($resultsArr,$error);
                    }
                }
            }
        }
    }// end of line 83
}
//after checking everything within the parent components, check for the items outside the parent component
foreach($layer1Items as $itemKey => $item){
    $itemID = $item[1]["id"];
    $itemGeo = $item[1]->children();
    $itemStartX =(int) $itemGeo[0]["x"];
    $itemStartY = (int)$itemGeo[0]["y"];
    $itemWidth =(int) $itemGeo[0]["width"];
    $itemHeight =(int)$itemGeo[0]["height"];
    $itemEndX=(int) $itemWidth+ $itemStartX;
    $itemEndY=(int) $itemHeight+ $itemStartY;
    $itemIntersect = 1;//check if required or provided socket is connected to a component or parent component
    foreach($layer1Items as $siblingItem){
        $siblingItemID = $siblingItem[1]["id"];
        if((strcmp($siblingItemID,$itemID)!=false)){
            //do not check if the item are the same.
            $siblingItemGeo = $siblingItem[1]->children();
            $siblingItemStartX =(int) $siblingItemGeo[0]["x"];
            $siblingItemStartY = (int)$siblingItemGeo[0]["y"];
            $siblingItemWidth =(int) $siblingItemGeo[0]["width"];
            $siblingItemHeight =(int)$siblingItemGeo[0]["height"];
            $siblingItemEndX=(int) $siblingItemWidth+ $siblingItemStartX;
            $siblingItemEndY=(int) $siblingItemHeight+ $siblingItemStartY;
            //check if they intersects
            $styleElements = explode(";", $item[1]['style']);
            $itemRotatedArray= array();
            $itemRotation = 0;
            $itemCoordsArray= array();
            foreach($styleElements as $value) {
                if (strpos($value, 'rotation=') !== false) {
                    $itemRotation= (int) str_replace("rotation=","",$value);
                }
            }
            $styleElements = explode(";", $siblingItem[1]['style']);
            $siblingItemRotatedArray= array();
            $siblingItemRotation = 0;
            $siblingItemCoordsArray= array();
            foreach($styleElements as $value) {
                if (strpos($value, 'rotation=') !== false) {
                    $siblingItemRotation= (int) str_replace("rotation=","",$value);
                }
            }
            //get rotation for each component, compute the final coordinates
            $itemCoordsArray=getRotation($itemStartX,$itemStartY,$itemEndX,$itemEndY,$itemRotation,$itemCoordsArray,$item[0]);
            $siblingItemCoordsArray=getRotation($siblingItemStartX,$siblingItemStartY,$siblingItemEndX,$siblingItemEndY,$siblingItemRotation,$siblingItemCoordsArray,$siblingItem[0]);

            if(checkIntersect($itemCoordsArray,$siblingItemCoordsArray,1)){
                if((strcmp($item[0],"requireInterface")==false && (strcmp($siblingItem[0],"provideInterface")==false))||(strcmp($siblingItem[0],"requireInterface")==false && (strcmp($item[0],"provideInterface")==false))){
                    if(strcmp($item[0],"requireInterface")==false && (strcmp($siblingItem[0],"provideInterface")==false)){
                        if(checkIntersect($itemCoordsArray,$siblingItemCoordsArray,2)){
                            //Required interface and provided interface is connected.
                           // $error = array($itemID,"Connected Type A");
                           // array_push($resultsArr,$error);
                        }else{
                            //head of required not connected with head of provided
                            $error = array($itemID,"The required interface of ID: ".$itemID." connection is not connected properly with provided interface of ID: ".$siblingItemID);
                            array_push($resultsArr,$error);
                        }
                    }else if(checkIntersect($itemCoordsArray,$siblingItemCoordsArray,3)){
                        //Provided interface and provided interface is connected
                        //$error = array($itemID,"Connected Type B");
                        //array_push($resultsArr,$error);
                        }else{
                            $error = array($itemID,"The provided interface of ID: ".$itemID." connection is not connected properly with Required interface of ID: ".$siblingItemID);
                            array_push($resultsArr,$error);
                        }
                }else if((strcmp($item[0],"port")==false) && ((strcmp($siblingItem[0],"provideInterface")==false) || (strcmp($siblingItem[0],"requireInterface")==false) || (strcmp($siblingItem[0],"component")==false))){
                        if((strcmp($siblingItem[0],"component")==false)){
                            $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." has been overlapped by ".$siblingItem[0]." object of ID: ".$siblingItem[1]["id"].". Port needs to be above the component and along it's border." );
                            array_push($resultsArr,$error);
                        }else if((strcmp($siblingItem[0],"provideInterface")==false)){
                                //check for provide interface
                                if(!checkIntersect($itemCoordsArray,$siblingItemCoordsArray, 5)){
                                 //there is overlap
                                 $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." is not connected to the tail of ".$siblingItem[0]." object of ID: ".$siblingItem[1]["id"]."." );
                                array_push($resultsArr,$error);
                                }
                        }else if((strcmp($siblingItem[0],"requireInterface")==false)){
                            //check for require interface
                            if(checkIntersect($componentFinalCoordinates,$myRotatedArray, 4)){
                                //there is overlapp
                                $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]."is not connected to the tail of ".$siblingItem[0]." object of ID: ".$siblingItem[1]["id"]."." );
                                array_push($resultsArr,$error);
                            }
                        }
                    }else if((strcmp($item[0],"component")==false) && ((strcmp($siblingItem[0],"provideInterface")==false) || (strcmp($siblingItem[0],"requireInterface")==false) || (strcmp($siblingItem[0],"port")==false))){
                        if(!(strcmp($siblingItem[0],"port")==false)){    
                            $tempComponentStartX = $itemStartX + 5;
                            $temptComponentStartY = $itemStartY + 5; 
                            $temptComponentEndX = $itemEndX - 5;
                            $temptComponentEndY = $itemEndY - 5;
                            $tempItemCoordsArray = array();
                            $tempItemCoordsArray=getRotation($tempComponentStartX,$temptComponentStartY,$temptComponentEndX,$temptComponentEndY,$itemRotation,$tempItemCoordsArray,$item[0]);
                            if((strcmp($siblingItem[0],"requireInterface")==false)){
                                //check for require interface
                                if(checkIntersect($itemCoordsArray,$siblingItemCoordsArray, 4)){
                                    //there is overlapp
                                    //do nothing
                                    if(checkIntersect($tempItemCoordsArray,$siblingItemCoordsArray, 1)){
                                        $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." has overlap with ".$siblingItem[0]." object of ID: ".$siblingItem[1]["id"]);
                                        array_push($resultsArr,$error);
                                    }
                                }else {
                                    //echo "Connected";
                                }
                            }else if((strcmp($siblingItem[0],"provideInterface")==false)){
                                //check for provide interface
                                if(checkIntersect($itemCoordsArray,$siblingItemCoordsArray, 5)){
                                //there is overlapp
                                //doing nothing
                                    if(checkIntersect($tempItemCoordsArray,$siblingItemCoordsArray, 1)){
                                        $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." has overlap with ".$siblingItem[0]." object of ID: ".$siblingItem[1]["id"]);
                                        array_push($resultsArr,$error);
                                    }
                                }
                            }
                        }else{
                            //port checking
                            $negativeCount = 0; 
                            $positiveCount = 0;
                            $portCoordinates = array();
                            if($itemStartX>$siblingItemStartX){
                                $negativeCount++;
                            }
                            if($itemEndX<$siblingItemEndX){
                                $negativeCount++;
                            }
                            if($itemStartY>$siblingItemStartY){
                                $negativeCount++;
                            }
                            if($itemEndY<$siblingItemEndY){
                                $negativeCount++;
                            }           
                            /*$error = array($item[1]["id"],"negative  ".$negativeCount." positive".$positiveCount." is not along the border of parent component ID:".$item[1]["id"]);
                            array_push($resultsArr,$error);*/
                            if(!($negativeCount==1)){
                                //check if the location of the port is at the right position
                                if(!$negativeCount==2){
                                    $error = array($item[1]["id"],"The location of ".$siblingItem[0]." child object of ID:".$siblingItemID." is not along the border of component ID: ".$itemID);
                                    array_push($resultsArr,$error);
                                }else{
                                    $error = array($item[1]["id"],"The location of ".$siblingItem[0]." child object of ID:".$siblingItemID." at the edge of component ID: ".$itemID);
                                    array_push($resultsArr,$error);
                                }
                            }
                        }
                    }else if(strcmp($item[0],"provideInterface")==false){
                            //need to check for parent component
                            if(strcmp($siblingItem[0],"parentComponent")==false ||strcmp($siblingItem[0],"component")==false){
                                    $tempComponentStartX = $siblingItemStartX + 5;
                                    $temptComponentStartY = $siblingItemStartY + 5; 
                                    $temptComponentEndX = $siblingItemEndX - 5;
                                    $temptComponentEndY = $siblingItemEndY - 5;
                                    $tempItemCoordsArray = array();
                                    $tempItemCoordsArray=getRotation($tempComponentStartX,$temptComponentStartY,$temptComponentEndX,$temptComponentEndY,$siblingItemRotation,$tempItemCoordsArray,$siblingItem[0]);
                                    if(checkIntersect($siblingItemCoordsArray ,$itemCoordsArray,5)){
                                        //there is overlapp
                                        //do nothing
                                        if(checkIntersect($tempItemCoordsArray ,$itemCoordsArray,1)){
                                            $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." has overlap with ".$siblingItem[0]." object of ID: ".$siblingItem[1]["id"]);
                                            array_push($resultsArr,$error);
                                            $itemIntersect = 0;
                                        }else{
                                            $itemIntersect = 0;
                                        }
                                    }else {
                                        //echo "Connected";
                                    }
                            }else if(strcmp($siblingItem[0],"requireInterface")==false){
                            //check for required interface type 3
                                if(!checkIntersect($tempItemCoordsArray,$siblingItemCoordsArray, 3)){
                                    $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." is not correctly connected to ".$siblingItem[0]." object of ID: ".$siblingItem[1]["id"]);
                                    array_push($resultsArr,$error);
                                }
                            }
                    }else if(strcmp($item[0],"requireInterface")==false){
                        if(strcmp($siblingItem[0],"parentComponent")==false ||strcmp($siblingItem[0],"component")==false){
                            $tempComponentStartX = $siblingItemStartX + 5;
                            $temptComponentStartY = $siblingItemStartY + 5; 
                            $temptComponentEndX = $siblingItemEndX - 5;
                            $temptComponentEndY = $siblingItemEndY - 5;
                            $tempItemCoordsArray = array();
                            $tempItemCoordsArray=getRotation($tempComponentStartX,$temptComponentStartY,$temptComponentEndX,$temptComponentEndY,$siblingItemRotation,$tempItemCoordsArray,$siblingItem[0]);
                            if(checkIntersect($siblingItemCoordsArray, $itemCoordsArray,4)){
                                //there is overlapp
                                if(checkIntersect($tempItemCoordsArray, $itemCoordsArray, 1)){
                                    $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." has overlap with ".$siblingItem[0]." object of ID: ".$siblingItem[1]["id"]);
                                    array_push($resultsArr,$error);
                                    $itemIntersect = 0;
                                }else{
                                    $itemIntersect = 0;
                                }
                            }else {
                                //echo "Connected";
                            }

                        }else if(strcmp($siblingItem[0],"provideInterface")==false){
                                if(!checkIntersect($tempItemCoordsArray,$siblingItemCoordsArray, 2)){
                                    $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." is not correctly connected to  ".$siblingItem[0]." object of ID: ".$siblingItem[1]["id"]);
                                    array_push($resultsArr,$error);
                                    }
                                }
                    }else{
                    // echo "item overlapp item"
                    // given that both connectors are not the same type
                    $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." has overlap with ".$siblingItem[0]." object of ID: ".$siblingItem[1]["id"]);
                    array_push($resultsArr,$error);
                    }
            }
        }
    }
    if ($itemIntersect > 0 && strcmp($item[0],"requireInterface")==false){
        $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." is not connected to any component or port of a parent component.");
        array_push($resultsArr,$error);
    }else if ($itemIntersect > 0 && strcmp($item[0],"provideInterface")==false){
        $error = array($itemID,"The ".$item[0]." object of ID: ".$item[1]["id"]." is not connected to any component or port of a parent component.");
        array_push($resultsArr,$error);
    }
    unset($layer1Items[$itemKey]);
}

$resultsArr=checkItemRotation($legalItemList,$resultsArr);
//$resultsArr = array_unique($resultsArr);
echo json_encode($resultsArr);
function trimName($inName) {
    $componentName = str_replace("&nbsp;", "", $inName);
    $componentName = strip_tags($componentName);
    $componentName= trim($componentName);
    return $componentName;
}
//check if two items overlap with one another
function checkOverlap($aStartX,$aStartY,$aEndX,$aEndY,$bStartX,$bStartY,$bEndX,$bEndY){
    if(($aStartX<$bEndX) && ($aEndX >$bStartX) && ($aStartY<$bEndY) && ($aEndY> $bStartY))
    {
        return false;
    }else{
        return true;
    }
}
//check rotation of the object
function checkItemRotation($itemList,$resultsArr){
    foreach($itemList as $item){
        if(strcmp($item[0],"requireInterface")==true){
            if(strcmp($item[0],"provideInterface")==true){
                $styleElements = explode(";", $item[1]['style']);
                foreach($styleElements as $value) {
                    if (strpos($value, 'rotation=') !== false) {
                        if (strcmp($value, 'rotation=0')) {
                            //$error = array($item[1]["id"],$item[0]." of ID: ".$item[1]['id']." has a rotation that is not 0 which is incorrect.".$value."and".strcmp($value, 'rotation=0'));
                            $error = array($item[1]["id"],$item[0]." of ID: ".$item[1]['id']." has a rotation that is not 0 which is incorrect.");
                            array_push($resultsArr,$error);
                        }
                    }
                }
            }
        }
    }
    return $resultsArr;
}

function checkIntersect($coordinatesA, $coordinatesB,$type){
    $r1 = array();
    $r2 = array();
    if($type==1){
        for($i =0;$i<4; $i++){
            for($j =0;$j<2; $j++){
            array_push($r1,$coordinatesA[$i][$j]);
            }
        }
        for($i =0;$i<4; $i++){
            for($j =0;$j<2; $j++){
            array_push($r2,$coordinatesB[$i][$j]);
            }
        }
    }else if($type ==3){
        for($i =8;$i<12; $i++){
            for($j =0;$j<2; $j++){
            array_push($r1,$coordinatesA[$i][$j]);
            }
        }
        for($i =4;$i<8; $i++){
            for($j =0;$j<2; $j++){
            array_push($r2,$coordinatesB[$i][$j]);
            }
        }
    }else if($type ==2){
        for($i =4;$i<8; $i++){
            for($j =0;$j<2; $j++){
            array_push($r1,$coordinatesA[$i][$j]);
            }
        }
        for($i =8;$i<12; $i++){
            for($j =0;$j<2; $j++){
            array_push($r2,$coordinatesB[$i][$j]);
            }
        }
    }else if($type == 5){
        //component intersects provided
        for($i =0;$i<4; $i++){
            for($j =0;$j<2; $j++){
            array_push($r1,$coordinatesA[$i][$j]);
            }
        }
        for($i =4;$i<8; $i++){
            for($j =0;$j<2; $j++){
            array_push($r2,$coordinatesB[$i][$j]);
            }
        }
    }else if($type == 4){
        //component intersects required
        for($i =0;$i<4; $i++){
            for($j =0;$j<2; $j++){
            array_push($r1,$coordinatesA[$i][$j]);
            }
        }
        for($i =8;$i<12; $i++){
            for($j =0;$j<2; $j++){
            array_push($r2,$coordinatesB[$i][$j]);
            }
        }
    }
    return isecrects($r1, $r2);
}


function edgeTest($p1, $p2, $p3, $r2) {
    $rot = array(-($p2[1] - $p1[1]),
                  $p2[0] - $p1[0]);

    $ref = ($rot[0] * ($p3[0] - $p1[0]) +
               $rot[1] * ($p3[1] - $p1[1])) >= 0;

    for ($i = 0, $il = count($r2); $i < $il; $i+=2) {
        if ((($rot[0] * ($r2[$i]   - $p1[0]) + $rot[1] * ($r2[$i+1] - $p1[1])) >= 0) === $ref){ return false;}
    }

    return true;
}
function isecrects($r1, $r2) {
    //if (!$r1 || !$r2) th$row new E$r$ro$r('$rects a$re not access$ible');

    
    $pn; $px;
    for ($pi = 0, $pl = count($r1); $pi < $pl; $pi += 2) {
        $pn = ($pi === ($pl - 2)) ? 0 : $pi + 2; // next $po$int
        $px = ($pn === ($pl - 2)) ? 0 : $pn + 2;
        if (edgeTest([$r1[$pi], $r1[$pi+1]],
                     [$r1[$pn], $r1[$pn+1]],
                     [$r1[$px], $r1[$px+1]], $r2)){ return false;}
    }
    for ($pi = 0, $pl = count($r2); $pi < $pl; $pi += 2) {
        $pn = ($pi === ($pl - 2)) ? 0 : $pi + 2; // next $point
        $px = ($pn === ($pl - 2)) ? 0 : $pn + 2;
        if (edgeTest([$r2[$pi], $r2[$pi+1]],
                     [$r2[$pn], $r2[$pn+1]],
                     [$r2[$px], $r2[$px+1]], $r1)){return false;}
    }
    return true;
}

function getRotation($startX,$startY,$endX,$endY,$rotation,$coordinatesArr,$type) {
    //return the rotated coordinates array
    $rotation= $rotation * 1;
    $h = ($startX + $endX)/2;
    $k = ($endY + $startY)/2;
    $r = deg2rad($rotation);

    //Start Top Left Point - Move To Origin
    $tempStartTLX= $startX- $h;
    $tempStartTLY = $startY- $k;
    //Start Top Right Point;
    $tempStartTRX= $endX- $h;
    $tempStartTRY = $startY- $k;
    //Start Bootom Left Point
    $tempStartBLX= $startX- $h;
    $tempStartBLY = $endY- $k;
    //Start Bottom Right Point
    $tempEndBRX = $endX - $h;
    $tempEndBRY = $endY -$k;
    if(strcmp($type,"provideInterface")==false){
        $rectLHSStartx = $startX-3;
        $rectLHSStarty = $k - (5);
        //$rectLHSEndx = $startX +15 ;
        $rectLHSEndx = $startX;
        $rectLHSEndy = $k + (5);

        $rectRHSStartx = $endX -10;
        $rectRHSStarty = $k - (15);
        $rectRHSEndx = $endX;
        $rectRHSEndy = $k + (15);

    }else{
        $rectLHSStartx = $startX ;
        $rectLHSStarty = $k - (15);
        $rectLHSEndx = $startX +10;
        $rectLHSEndy = $k + (15);

        $rectRHSStartx = $endX;
        $rectRHSStarty = $k - (5);
        $rectRHSEndx = $endX+3;
        $rectRHSEndy = $k + (5);
    }
    /*
    //Start Top Left Point - Move To Origin
    $tempStartTLX= $startX- $h;
    $tempStartTLY = $startY- $k;
    //Start Top Right Point;
    $tempStartTRX= $endX- $h;
    $tempStartTRY = $startY- $k;
    //Start Bootom Left Point
    $tempStartBLX= $startX- $h;
    $tempStartBLY = $endY- $k;
    //Start Bottom Right Point
    $tempEndBRX = $endX - $h;
    $tempEndBRY = $endY -$k;
*/
    //Start Top Left Point - Rotate shape
    $rotatedStartTLX = $tempStartTLX*cos($r) - $tempStartTLY*sin($r);
    $rotatedStartTLY = $tempStartTLX*sin($r) + $tempStartTLY*cos($r);
    //Start Top Right Point;
    $rotatedStartTRX = $tempStartTRX*cos($r) - $tempStartTRY*sin($r);
    $rotatedStartTRY = $tempStartTRX*sin($r) + $tempStartTRY*cos($r);
    //Start Bottom Right Point
    $rotatedStartBRX = $tempEndBRX*cos($r) - $tempEndBRY*sin($r);
    $rotatedStartBRY = $tempEndBRX*sin($r) + $tempEndBRY*cos($r);
    //Start Bootom Left Point
    $rotatedStartBLX = $tempStartBLX*cos($r) - $tempStartBLY*sin($r);
    $rotatedStartBLY = $tempStartBLX*sin($r) + $tempStartBLY*cos($r);


    //Start Top Left Point + Move To old location
    $rotatedStartTLX= $rotatedStartTLX+ $h;
    $rotatedStartTLY = $rotatedStartTLY+ $k;
    $coordinates = array($rotatedStartTLX,$rotatedStartTLY);
    array_push($coordinatesArr,$coordinates);
    //Start Top Right Point;
    $rotatedStartTRX= $rotatedStartTRX+ $h;
    $rotatedStartTRY = $rotatedStartTRY+ $k;
    $coordinates = array($rotatedStartTRX,$rotatedStartTRY);
    array_push($coordinatesArr,$coordinates);
    //Start Bottom Right Point
    $rotatedStartBRX = $rotatedStartBRX + $h;
    $rotatedStartBRY = $rotatedStartBRY +$k;
    $coordinates = array($rotatedStartBRX,$rotatedStartBRY);
    array_push($coordinatesArr,$coordinates);
    //Start Bootom Left Point
    $rotatedStartBLX= $rotatedStartBLX+ $h;
    $rotatedStartBLY = $rotatedStartBLY+ $k;
    $coordinates = array($rotatedStartBLX,$rotatedStartBLY);
    array_push($coordinatesArr,$coordinates);

    
    //LHS Rectangle
    //Start Top Left Point - Move To Origin
    $temprectLHSStartTLx = $rectLHSStartx-$h;
    $temprectLHSStartTLy = $rectLHSStarty-$k;
    //Start Top Right Point;
    $temprectLHSStartTRx = $rectLHSEndx-$h;
    $temprectLHSStartTRy = $rectLHSStarty-$k;
    //Start Bottom Left Point
    $temprectLHSStartBLx = $rectLHSStartx-$h;
    $temprectLHSStartBLy = $rectLHSEndy-$k;
    //Start Bottom Right Point
    $temprectLHSEndBRx =  $rectLHSEndx - $h;
    $temprectLHSEndBRy = $rectLHSEndy-$k;

    //Start Top Left Point - Rotate shape
    $rotatedLHSStartTLX = $temprectLHSStartTLx*cos($r) - $temprectLHSStartTLy*sin($r);
    $rotatedLHSStartTLY = $temprectLHSStartTLx*sin($r) + $temprectLHSStartTLy*cos($r);
    //Start Top Right Point;
    $rotatedLHSStartTRX = $temprectLHSStartTRx*cos($r) - $temprectLHSStartTRy*sin($r);
    $rotatedLHSStartTRY = $temprectLHSStartTRx*sin($r) + $temprectLHSStartTRy*cos($r);
    //Start Bottom Right Point
    $rotatedLHSStartBRX = $temprectLHSEndBRx*cos($r) - $temprectLHSEndBRy*sin($r);
    $rotatedLHSStartBRY = $temprectLHSEndBRx*sin($r) + $temprectLHSEndBRy*cos($r);
    //Start Bootom Left Point
    $rotatedLHSStartBLX = $temprectLHSStartBLx*cos($r) - $temprectLHSStartBLy*sin($r);
    $rotatedLHSStartBLY = $temprectLHSStartBLx*sin($r) + $temprectLHSStartBLy*cos($r);

    //Start Top Left Point + Move To old location
    $rotatedLHSStartTLX= $rotatedLHSStartTLX+ $h;
    $rotatedLHSStartTLY = $rotatedLHSStartTLY+ $k;
    $coordinates = array($rotatedLHSStartTLX,$rotatedLHSStartTLY);
    array_push($coordinatesArr,$coordinates);
    //Start Top Right Point;
    $rotatedLHSStartTRX= $rotatedLHSStartTRX+ $h;
    $rotatedLHSStartTRY = $rotatedLHSStartTRY+ $k;
    $coordinates = array($rotatedLHSStartTRX,$rotatedLHSStartTRY);
    array_push($coordinatesArr,$coordinates);
    //Start Bottom Right Point
    $rotatedLHSStartBRX = $rotatedLHSStartBRX + $h;
    $rotatedLHSStartBRY = $rotatedLHSStartBRY +$k;
    $coordinates = array($rotatedLHSStartBRX,$rotatedLHSStartBRY);
    array_push($coordinatesArr,$coordinates);
    //Start Bootom Left Point
    $rotatedLHSStartBLX= $rotatedLHSStartBLX+ $h;
    $rotatedLHSStartBLY = $rotatedLHSStartBLY+ $k;
    $coordinates = array($rotatedLHSStartBLX,$rotatedLHSStartBLY);
    array_push($coordinatesArr,$coordinates);


    //RHS Rectangle
    //Start Top Left Point - Move To Origin
    $temprectRHSStartTLx = $rectRHSStartx-$h;
    $temprectRHSStartTLy = $rectRHSStarty-$k;
    //Start Top Right Point;
    $temprectRHSStartTRx = $rectRHSEndx-$h;
    $temprectRHSStartTRy = $rectRHSStarty-$k;
    //Start Bottom Left Point
    $temprectRHSStartBLx = $rectRHSStartx-$h;
    $temprectRHSStartBLy = $rectRHSEndy-$k;
    //Start Bottom Right Point
    $temprectRHSEndBRx =  $rectRHSEndx - $h;
    $temprectRHSEndBRy = $rectRHSEndy-$k;

    //Start Top Left Point - Rotate shape
    $rotatedRHSStartTLX = $temprectRHSStartTLx*cos($r) - $temprectRHSStartTLy*sin($r);
    $rotatedRHSStartTLY = $temprectRHSStartTLx*sin($r) + $temprectRHSStartTLy*cos($r);
    //Start Top Right Point;
    $rotatedRHSStartTRX = $temprectRHSStartTRx*cos($r) - $temprectRHSStartTRy*sin($r);
    $rotatedRHSStartTRY = $temprectRHSStartTRx*sin($r) + $temprectRHSStartTRy*cos($r);
    //Start Bootom Left Point
    $rotatedRHSStartBLX = $temprectRHSStartBLx*cos($r) - $temprectRHSStartBLy*sin($r);
    $rotatedRHSStartBLY = $temprectRHSStartBLx*sin($r) + $temprectRHSStartBLy*cos($r);
    //Start Bottom Right Point
    $rotatedRHSStartBRX = $temprectRHSEndBRx*cos($r) - $temprectRHSEndBRy*sin($r);
    $rotatedRHSStartBRY = $temprectRHSEndBRx*sin($r) + $temprectRHSEndBRy*cos($r);

    //Start Top Left Point + Move To old location
    $rotatedRHSStartTLX= $rotatedRHSStartTLX+ $h;
    $rotatedRHSStartTLY = $rotatedRHSStartTLY+ $k;
    $coordinates = array($rotatedRHSStartTLX,$rotatedRHSStartTLY);
    array_push($coordinatesArr,$coordinates);
    //Start Top Right Point;
    $rotatedRHSStartTRX= $rotatedRHSStartTRX+ $h;
    $rotatedRHSStartTRY = $rotatedRHSStartTRY+ $k;
    $coordinates = array($rotatedRHSStartTRX,$rotatedRHSStartTRY);
    array_push($coordinatesArr,$coordinates);
    //Start Bottom Right Point
    $rotatedRHSStartBRX = $rotatedRHSStartBRX + $h;
    $rotatedRHSStartBRY = $rotatedRHSStartBRY +$k;
    $coordinates = array($rotatedRHSStartBRX,$rotatedRHSStartBRY);
    array_push($coordinatesArr,$coordinates);
    //Start Bootom Left Point
    $rotatedRHSStartBLX= $rotatedRHSStartBLX+ $h;
    $rotatedRHSStartBLY = $rotatedRHSStartBLY+ $k;
    $coordinates = array($rotatedRHSStartBLX,$rotatedRHSStartBLY);
    array_push($coordinatesArr,$coordinates);

    //headsize width=10 height=12
   /*echo "Your rotation is ".$r."|||".$h."||".$k."||";
    echo $startX." k ".$startY." k ".$endX." k ".$endY." k ".$rotation."||||";

    foreach($coordinatesArr as $test){
        echo "coord".$test[0];
        echo "coord".$test[1];
    }*/

    return $coordinatesArr;
}
?>