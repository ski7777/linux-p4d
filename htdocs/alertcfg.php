<?php

include("header.php");

printHeader();

include("setup.php");

// -------------------------
// chaeck login

if (!haveLogin())
{
   echo "<br/><div class=\"infoError\"><b><center>Login erforderlich!</center></b></div><br/>\n";
   die("<br/>");
}

// -------------------------
// establish db connection

$mysqli = new mysqli($mysqlhost, $mysqluser, $mysqlpass, $mysqldb, $mysqlport);
$mysqli->query("set names 'utf8'");
$mysqli->query("SET lc_time_names = 'de_DE'");

// ------------------
// variables

$action = "";

// ------------------
// get post

if (isset($_POST["action"]))
   $action = htmlspecialchars($_POST["action"]);

if ($action == "store")
{
   // store settings

   $ID    = explode("|", $_POST["cnt"]);

   for ($i = 1; $i <= intval($_POST["id"]); $i++)
   {
      $adr   = "Adr(" . $ID[$i] . ")";
      $type  = "Type(" . $ID[$i] . ")";
      $min   = "min(" . $ID[$i] . ")";
      $max   = "max(" . $ID[$i] . ")";
      $int   = "Int(" . $ID[$i] . ")";
      $delta = "Delta(" . $ID[$i] . ")";
      $range = "Range(" . $ID[$i] . ")";
      $madr  = "MAdr(" . $ID[$i] . ")";
      $msub  = "MSub(" . $ID[$i] . ")";
      $mbod  = "MBod(" . $ID[$i] . ")";
      $act   = ($_POST["Act($ID[$i])"]) ? "A" : "D";
      $time = time();
      $body =  $mysqli->real_escape_string($_POST[$mbod]);
      $subject = $mysqli->real_escape_string($_POST[$msub]);

      $data = " address='$_POST[$adr]', type='" . mb_strtoupper($_POST[$type]) . "', min='$_POST[$min]', max='$_POST[$max]', "
         . "maxrepeat='$_POST[$int]', delta='$_POST[$delta]', rangem='$_POST[$range]', "
         . "maddress='$_POST[$madr]', msubject='$subject', mbody='$body', state='$act' ";

      if ($i == count($ID)-1 && $_POST[$adr] != "")
      {
         $stmt = "insert into sensoralert set inssp=$time, updsp=$time, ";
         $mysqli->query($stmt . $data)
            or die("<br/>Error: " . $mysqli->error);
      }

      if ($i < count($ID)-1)
      {
         $stmt = "update sensoralert set updsp=$time, ";
         $where = "where id=" . $ID[$i];
         $mysqli->query($stmt . $data . $where)
            or die("<br/>Error: " . $mysqli->error);
      }
   }
}

else if (substr($action, 0, 6) == "delete")
{
   // delete entry

   $mysqli->query("delete from sensoralert where id=" . substr($action, 6))
      or die("<br/>Error: " . $mysqli->error);
}

else if (substr($action, 0, 8) == "mailtest")
{
   $resonse = "";

   if (sendTestMail("", "", $resonse, substr($action, 8)))
      echo "      <br/><div class=\"info\"><b><center>Mail Test succeeded</center></div><br/>\n";
   else
      echo "      <br/><div class=\"infoError\"><b><center>Sending Mail failed '$resonse' - p4d log for further details</center></div><br/>\n";
}

// ------------------
// setup form

$i = 0; $cnt = "0";

echo "      <form action=" . htmlspecialchars($_SERVER["PHP_SELF"]) . " method=post>\n";
echo "        <div class=\"menu\" style=\"position: fixed; top=88px;\">\n";
echo "          <button class=\"rounded-border button3\" type=\"submit\" name=\"action\" value=\"store\">Speichern</button>\n";
echo "        </div>\n";
echo "        <div class=\"menu\" style=\"top=88px;\">\n";
echo "        </div>\n";

// ------------------------
// setup items ...

seperator("Benachrichtigungen bei bestimmten Sensor-Werten", 0);
seperator("Bedingungen<span class=\"help\" onClick=\"showContent('hlp')\">[Hilfe]</span>", 0, "seperatorTitle2");

echo "        <div class=\"rounded-border inputTable\" id=\"hlp\" style=\"display:none;\">\n";
echo "          <span class=\"inputComment\">
                Hier werden die die Bedingungen für die Alarmwerte der einzelnen Sensoren definiert. Dabei gilt wieder: Sensor-ID und -Typ aus der Tabelle <br/>
                'Aufzeichnung' entnehmen und hier eintragen.<br/><br/>
                <b>Beispiel:</b> Nachricht wenn die Kesselstellgröße unter 50% sinkt, oder sich mehr als 10% in 1min ändert, aber nicht öfter als alle 5min.<br/>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <b>ID:18&nbsp;&nbsp; Typ:VA&nbsp;&nbsp; min:50&nbsp;&nbsp;
                max:100&nbsp;&nbsp; Intervall:5&nbsp;&nbsp; Änderung:10&nbsp;&nbsp; Zeitraum:1</b><br/>
                <b>Zulässige Werte:</b><br/><b>ID:</b> Zahl (auch Hex) | <b>min, max, Änderung:</b> Zahl | <b>
                Intervall, Zeitraum:</b> Zahl (Minuten)<br/><br/>
                für Betreff und Text können folgende Platzhalter verwendet werden:<br/>
                %sensorid% %title% %value% %unit% %min% %max% %repeat% %delta% %range% %time% %weburl%<br/>
                mit 'aktiv' aktivierst oder deaktivierst du nur die Benachrichtigung, auf die Steuerung hat dies keinen Einfluss\n
               </span>\n";
echo "        </div>\n";

$result = $mysqli->query("select * from sensoralert")
   or die("<br/>Error: " . $mysqli->error);

while ($row = $result->fetch_array(MYSQLI_ASSOC))
{
   $ID =  $row['id'];
   $i++;
   $cnt = $cnt . "|" . $row['id'];
   $style = ($row['state'] == "D") ? "; background-color:#ddd\" readOnly=\"true" : "";

   displayAlertConfig($ID, $row, $style);
}

$mysqli->close();
$ID++;
$cnt = $cnt . "|" . $ID;

displayAlertConfig($ID, $row, "");

echo "        <input type=hidden name=id value=" . ($i+1) . ">\n";
echo "        <input type=hidden name=cnt value=" . $cnt . ">\n";
echo "      </form>\n";

include("footer.php");

//***************************************************************************
// Display Alert Config Block
//***************************************************************************

function displayAlertConfig($ID, $row, $style)
{
   $a = chr($ID+64);

   echo "        <div class=\"rounded-border inputTable\">\n";
   echo "         <div>\n";
   echo "           <span>Aktiv</span>\n";
   echo "           <span><input type=checkbox name=Act(" . $ID . ")" .(($row['state'] == "A") ? " checked" : "") . " onClick=\"readonlyContent('$a',this)\" onLoad=\"disableContent('$a',this)\"></input></span>\n";
   echo "           <span><button class=\"rounded-border button2\" type=\"submit\" id=\"$a\" name=\"action\" value=\"mailtest$ID\">Test Mail</button></span>\n";
   echo "           <span><button class=\"rounded-border button2\" type=\"submit\" name=\"action\" value=\"delete$ID\" onclick=\"return confirmSubmit('diesen Eintrag wirklich löschen?')\">Löschen</button></span>\n";
   echo "         </div>\n";
   echo "         <div>\n";
   echo "           <span>Intervall:</span>\n";
   echo "           <span><input class=\"rounded-border input\" style=\"width:60px$style\" type=\"text\" id=\"$a\" name=\"Int(" . $ID . ")\"   value=\"" . $row['maxrepeat'] . "\"></input> Minuten</span>\n";
   echo "         </div>\n";
   echo "         <div>\n";
   echo "           <span>ID:</span>\n";
   echo "           <span><input class=\"rounded-border input\" style=\"width:60px$style\" type=\"text\" id=\"$a\" name=\"Adr(" . $ID . ")\"   value=\"" . $row['address'] . "\"></input></span>\n";
   configOptionItem(5, "Typ", "Type(" . $ID . ")", $row['type'], "VA:VA UD:US DI:DI DO:DO W1:W1", "", "id=\"$a\" style=\"width:60px$style\"");
   echo "         </div>\n";

   echo "         <div>\n";
   echo "           <span>Minimum:</span>\n";
   echo "           <span><input class=\"rounded-border input\" style=\"width:60px$style\" type=\"text\" id=\"$a\" name=\"min(" . $ID . ")\"   value=\"" . $row['min'] . "\"></input></span>\n";
   echo "           <span>Maximum:</span>\n";
   echo "           <span><input class=\"rounded-border input\" style=\"width:60px$style\" type=\"text\" id=\"$a\" name=\"max(" . $ID . ")\"   value=\"" . $row['max'] . "\"></input></span>\n";
   echo "         </div>\n";

   echo "         <div>\n";
   echo "           <span>Änderung:</span>\n";
   echo "           <span><input class=\"rounded-border input\" style=\"width:60px$style\" type=\"text\" id=\"$a\" name=\"Delta(" . $ID . ")\" value=\"" . $row['delta'] . "\"></input> %</span>\n";
   echo "           <span>im Zeitraum:</span>\n";
   echo "           <span><input class=\"rounded-border input\" style=\"width:60px$style\" type=\"text\" id=\"$a\" name=\"Range(" . $ID . ")\" value=\"" . $row['rangem'] . "\"></input> Minuten</span>\n";
   echo "         </div>\n";

   echo "         <div>\n";
   echo "           <span>Empfänger:</span>\n";
   echo "           <span><input class=\"rounded-border input\" style=\"width:805px$style\" type=\"text\" id=\"$a\" name=\"MAdr(" . $ID . ")\"  value=\"" . $row['maddress'] . "\"></input></span>\n";
   echo "         </div>\n";
   echo "         <div>\n";
   echo "           <span>Betreff:</span>\n";
   echo "           <span><input class=\"rounded-border input\" style=\"width:805px$style\" type=\"text\" id=\"$a\" name=\"MSub(" . $ID . ")\"  value=\"" . $row['msubject'] . "\"></input></span>\n";
   echo "         </div>\n";
   echo "         <div>\n";
   echo "           <span>Inhalt:</span>\n";
   echo "           <span><textarea class=\"rounded-border input\" rows=\"5\" style=\"width:805px$style\" id=\"$a\" name=\"MBod(" . $ID . ")\">" . $row['mbody'] . "</textarea></span>\n";
   echo "         </div>\n";
   echo "        </div>\n";
}

?>
