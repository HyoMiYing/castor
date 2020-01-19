<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <meta http-equiv="X-UA-Compatible" content="ie=edge">
   <link rel="stylesheet" href="style.css">
   <meta http-equiv="refresh" content="10">
   <title>Castor</title>
</head>
<body>
   
<?php    
   
   $ovenControllerIpAddress= "192.168.2.190";
   $ovenControllerIpPort= 4099;
   
   echo "<header>";
   echo "<h1>Castor Oven Monitoring Dashboard</h1>";
   
   // oven status data structure
   class OvenStatus {
      
      public $ovenId;
      public $temperature;
      public $switchStatus;
      public $statusTimestamp;
      
      /*public function __construct () {
         $this->ovenId= "non";
         $this->temperature= -273.5;
         $this->switchStatus= -1;
         $this->statusTimestamp= -1;
      }*/
      
      public function __construct ($anOvenId, $aTemperature, $aSwitchStatus, $aStatusTimestamp) {
         $this->ovenId= $anOvenId;
         $this->temperature= $aTemperature;
         $this->switchStatus= $aSwitchStatus;
         $this->statusTimestamp= $aStatusTimestamp;
      }
      
   }
   
   
   class CastorApiResponse {
      public $returnCode;
      //  0 = OK
      //  1 = initial value
      // -1, -2, -3, .... = specific error values
      public $returnText;
      // return or error text
      public $data;
      // data, returned by API
      
      public function __construct () {
         $this->returnCode= 1;
         $this->returnText= "initial value";
         $this->data= NULL;
      }
   }
   
   
   
   // API method getOvenStatus
   function getOvenStatus ($ovenId) {
      global $ovenControllerIpAddress, $ovenControllerIpPort;
      $apiResponse= new CastorApiResponse ();
      // send request to oven management module
      $socket= fsockopen ($ovenControllerIpAddress, $ovenControllerIpPort, $errno, $errstr, 15);
      $request= "getStatus;";
      if ($ovenId != null && $ovenId>0) {
         $request= $request . $ovenId;
      }
      fwrite ($socket, $request);
      // read response from oven management module
      $wholeStatus= "";
      while (!feof ($socket)) {
         $partialStatus= fgets ($socket, 1024);
         $wholeStatus= $wholeStatus . $partialStatus;
      }
      // parse response
      $allStatus= explode("#", $wholeStatus);
      // handle error reported from oven management module
      if (strtoupper (substr ($allStatus[0], 0, 2)) != "OK") {
         $apiResponse->returnCode= -1;
         $apiResponse->returnText= $allStatus[0];
         return $apiResponse;
      }
      // handle oven statuses
      $os= array ();
      foreach ($allStatus as $singleStatus) {
         //echo "singleStatus=#" . $singleStatus . "#<br>";
         // skip OK status
         if (strtoupper (substr ($singleStatus, 0, 2)) == "OK") {
            // do nothing
         }
         else if (strlen ($singleStatus) > 1)
         {
            $statusComponents= explode(";", $singleStatus);
            $os[]= new OvenStatus ($statusComponents[0], $statusComponents[1], $statusComponents[2], $statusComponents[3]);
         }
      }
      fclose ($socket);
      $apiResponse->returnCode= 0;
      $apiResponse->data= $os;
      return $apiResponse;
   }
   
   
   // API method setOvenSwitch
   function setOvenSwitch ($ovenId, $ovenSwitchState) {
      global $ovenControllerIpAddress, $ovenControllerIpPort;
      $apiResponse= new CastorApiResponse ();
      // ovenId parameter check 
      if ($ovenId == null && $ovenId<1) {
         $apiResponse->returnCode= -1;
         $apiResponse->returnText= 'Wrong oven identification: ' . $ovenId . ': Positive integer expected';
         return $apiResponse;
      }
      if ($ovenSwitchState != 0 && $ovenSwitchState != 1) {
         $apiResponse->returnCode= -1;
         $apiResponse->returnText= 'Wrong oven switch state: ' . $ovenSwitchState . ': Value 0 or 1 expected';
         return $apiResponse;
      }
      $request= "setOvenSwitch;" . $ovenId . ";" . $ovenSwitchState;
      $socket= fsockopen ($ovenControllerIpAddress, $ovenControllerIpPort, $errno, $errstr, 15);
      fwrite ($socket, $request);
      // read response from oven management module
      //echo 'response:\n';
      $wholeStatus= "";
      while (!feof ($socket)) {
         $partialStatus= fgets ($socket, 1024);
         $wholeStatus= $wholeStatus . $partialStatus;
      }
      //echo '<br>!' . $wholeStatus . '!<br>';    
      // parse response
      $allStatus= explode("#", $wholeStatus); 
      // handle error reported from oven management module
      if (strtoupper (substr ($allStatus[0], 0, 2)) != "OK") {
         $apiResponse->returnCode= -1;
         $apiResponse->returnText= $allStatus[0];
      }
      $apiResponse->returnCode= 0;
      return $apiResponse;
   }
   
   if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $apiResponse= setOvenSwitch (htmlspecialchars($_POST["ovenId"]), htmlspecialchars($_POST["desiredState"]));
    }


   // script execution start
   $ovensStatus= getOvenStatus (0);
   //var_dump($ovensStatus);
   if ($ovensStatus->returnCode != 0) {
      echo '<b>Oven status error:' . $ovensStatus->returnCode . ", " . $ovensStatus->returnText - '</b><br>';
   }
   else {   
      echo "</header>";

      echo "<div id='hero'>";
      echo "
         <div class='row' id='headings'>
            <div id='id-heading'><span>ID</span></div>
            <div id='temperature-heading'><span>TEMPERATURE</span></div>
            <div id='status-heading'><span>STATUS</span></div>
            <div id='timestamp-heading'><span>LAST REFRESH</span></div>
            <div id='action-heading'><span>ACTION</span></div>
         </div>
      ";
      foreach ($ovensStatus->data as $ovenStatus) {

         // UI PRINT

         // Oven ID
         $ovenIdUI = "<div class='oven-id'><span>" . $ovenStatus->ovenId . "</span></div>";

         // Oven temperature
         $ovenTemperatureUI = "<div class='oven-temperature'><span>" . $ovenStatus->temperature . "°C </span></div>";

         // Set color and text based on switch status
         if($ovenStatus->switchStatus) {
            $switchStatusDisplayedInUI = "<div class='oven-status oven-on'><span>ON</span></div>";
         } else {
            $switchStatusDisplayedInUI = "<div class='oven-status oven-off'><span>OFF</span></div>";
         }

         // Oven timestamp
         $ovenTimestampUI = "<div class='oven-timestamp'><span>" . strftime("%d.%m.%Y <br> %H:%M:%S", $ovenStatus->statusTimestamp) . "</span></div>";


         if($ovenStatus->switchStatus) {
            $desiredState = '0';
            $messageOnTheButton = 'Deactivate';
         } else {
            $desiredState = '1';
            $messageOnTheButton = 'Activate';
         }

         $ovenActionButtonUI = "<div class='oven-button'><span> 
            <form action='index.php' method='post'>
               <input type='number' name='ovenId' value=" . $ovenStatus->ovenId . " hidden>
               <input type='number' name='desiredState' value=" . $desiredState . " hidden>
               <input class='button' type='submit' value=" . $messageOnTheButton . ">
            </form></span></div>";


         echo
         "<div class='class-oven row'>"
            . $ovenIdUI
            . $ovenTemperatureUI
            . $switchStatusDisplayedInUI 
            . $ovenTimestampUI
            . $ovenActionButtonUI .
         "</div>";
      }
   }
   
   ?>
   </body>
   <script>
   // This code asks you to confirm your choice (activate/deactivate the oven)
   // If you don't want this function just comment out the code in <script><\/script> tags or delete it

   let elementsArray = document.querySelectorAll(".button");

   elementsArray.forEach(function(elem) {
      let rawText = elem.value;
      let text = rawText.toLowerCase();
      elem.addEventListener("click", function() {
         confirm("Proceeding to " + text + " the oven.");
      });
   });

   </script>
</html>