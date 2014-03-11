<?php
//This is required to get the international text strings dictionary
require_once 'internationalize.php';

//check authority to be here
require_once 'authorization_check2.php';

?>

<html>
<head>
<!--<title>HydroServer Lite Web Client</title>-->
<title><?php echo $WebClient; ?></title>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link rel="bookmark" href="favicon.ico" >

<link href="styles/main_css.css" rel="stylesheet" type="text/css" media="screen" />
<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?key=AIzaSyC3d042tZnUAA8256hCC2Y6QeTSREaxrY0&sensor=true"></script>    
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js" type="text/javascript"></script>
            
            
	<script type="text/javascript">
    //<![CDATA[

//Populate the Javascript Array

var initialLocation;
var siberia = new google.maps.LatLng(60, 105);
var newyork = new google.maps.LatLng(40.69847032728747, -73.9514422416687);
var browserSupportFlag =  new Boolean();
 var option_num=0;
    var map;
    var markers = [];
    var infoWindow;
    var locationSelect;
	var xml="-1";	

   function load() {
 
      map = new google.maps.Map(document.getElementById("map"), {
        center: new google.maps.LatLng(44, -160),
        zoom: 12,
        mapTypeId: 'roadmap',
        mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
      });
      infoWindow = new google.maps.InfoWindow();

      locationSelect = document.getElementById("locationSelect");
      locationSelect.onchange = function() {
        var markerNum = locationSelect.options[locationSelect.selectedIndex].value;
        if (markerNum != "none"){
          google.maps.event.trigger(markers[markerNum], 'mouseover');
        }
      };
	  
	  loadall();
   }

function track_loc(){
  // Try W3C Geolocation (Preferred)
  if(navigator.geolocation) {
    browserSupportFlag = true;
    navigator.geolocation.getCurrentPosition(function(position) {
      initialLocation = new google.maps.LatLng(position.coords.latitude,position.coords.longitude);
	   
	    var geocoder = new google.maps.Geocoder();
     geocoder.geocode({location: initialLocation}, function(results, status) {
       if (status == google.maps.GeocoderStatus.OK) {
        searchLocationsNear2(results[0].geometry.location);
       } else {
         //alert(address + ' not found');
		 alert(address +  <?php echo "'".$NotFound."'";?>)
       }
     });
  
	   
	   
    }, function() {
      handleNoGeolocation(browserSupportFlag);
    });
  }
  // Browser doesn't support Geolocation
   else {
    browserSupportFlag = false;
    handleNoGeolocation(browserSupportFlag);
  }
  
 
  
 // searchLocations2(initialLocation);
}

function loadall()
{
	clearLocations();
	option_num=0;
	 var searchUrl = 'db_display_all.php';
  downloadUrl(searchUrl, function(data) {
  var xml = parseXml(data);
  var markerNodes = xml.documentElement.getElementsByTagName("marker");
  var bounds = new google.maps.LatLngBounds();

   for (var i = 0; i < markerNodes.length; i++) {
	 
    var name = markerNodes[i].getAttribute("name");
    var sitecode = markerNodes[i].getAttribute("sitecode");
	var lat = markerNodes[i].getAttribute("lat");
	var long = markerNodes[i].getAttribute("lng");
    var distance = parseFloat(markerNodes[i].getAttribute("distance"));
    var latlng = new google.maps.LatLng(
        parseFloat(markerNodes[i].getAttribute("lat")),
        parseFloat(markerNodes[i].getAttribute("lng")));
	var type = markerNodes[i].getAttribute("sitetype");
	var siteid = markerNodes[i].getAttribute("siteid");
	    create_source(latlng, name, sitecode, type, lat, long, siteid, i);
	
    bounds.extend(latlng);
  }

  if (markerNodes.length == 1) {
      var center = bounds.getCenter();
	  var latlng1 = new google.maps.LatLng(center.lat + 0.001, center.lon + 0.001);
	  var latlng2 = new google.maps.LatLng(center.lat - 0.001, center.lon - 0.001);
	  //bounds.extend(latlng1);
	  //bounds.extend(latlng2);
	  map.setZoom(10);
  }

  map.fitBounds(bounds);
  map.panToBounds(bounds);
  map.setZoom(bounds);
	 });
	
	}
	

function create_source(latlng, name, sitecode, type, lat, long, siteid, i)
{
	//To Get The Sources Available on That Site

 var searchUrl_sources = 'db_source_search.php?siteid='+siteid;
	
	downloadUrl(searchUrl_sources, function(data) {
	 var xml5 = parseXml(data);
	 var sourcenodes = xml5.documentElement.getElementsByTagName("source");
	 var sourcename;
    var sourcecode;
	var sourcelink;
	 for (var j = 0; j <sourcenodes.length; j++) 
	 { 
	sourcename = sourcenodes[j].getAttribute("sourcename");
    sourcecode = sourcenodes[j].getAttribute("sourcecode");
	sourcelink = sourcenodes[j].getAttribute("sourcelink");
	 }
	 
	 if (sourcelink==undefined)
	 {sourcelink="";
		 }
	 
  if((sourcename!=undefined)&&(sourcecode!=undefined))
	{
	createMarker(latlng, name, sitecode, type, lat, long, sourcename, sourcecode, sourcelink, siteid);
	createOption(name, i, sourcename); 
	 }
	
	  });
	
	}

   function searchLocations() {
     var address = document.getElementById("addressInput").value;
     var geocoder = new google.maps.Geocoder();
     geocoder.geocode({address: address}, function(results, status) {
       if (status == google.maps.GeocoderStatus.OK) {
        searchLocationsNear(results[0].geometry.location);
       } else {
         //alert(address + ' not found');
		 alert(address + <?php echo "'".$NotFound."'";?>);
       }
     });
   }
   

   function clearLocations() {
     infoWindow.close();
     for (var i = 0; i < markers.length; i++) {
       markers[i].setMap(null);
     }
     markers.length = 0;

     locationSelect.innerHTML = "";
     var option = document.createElement("option");
     option.value = "none";
     option.innerHTML = "Click here for a list of Sites: ";
     locationSelect.appendChild(option);
   }

   function searchLocationsNear(center) {
     clearLocations(); 
	 option_num=0;

     var radius = document.getElementById('radiusSelect').value;
     var searchUrl = 'db_search.php?lat=' + center.lat() + '&lng=' + center.lng() + '&radius=' + radius;
     downloadUrl(searchUrl, function(data) {
       var xml2 = parseXml(data);
	   xml=xml2;
       var markerNodes = xml2.documentElement.getElementsByTagName("marker");
       var bounds = new google.maps.LatLngBounds();
       
	   if (markerNodes.length==0)
	   {alert("No Sites Found. Please Alter Search Terms");}
	   for (var i = 0; i < markerNodes.length; i++) {
        var name = markerNodes[i].getAttribute("name");
        var sitecode = markerNodes[i].getAttribute("sitecode");
	   	var lat = markerNodes[i].getAttribute("lat");
		var long = markerNodes[i].getAttribute("lng");
         var distance = parseFloat(markerNodes[i].getAttribute("distance"));
         var latlng = new google.maps.LatLng(
              parseFloat(markerNodes[i].getAttribute("lat")),
              parseFloat(markerNodes[i].getAttribute("lng")));var type = markerNodes[i].getAttribute("sitetype");
	var siteid = markerNodes[i].getAttribute("siteid");
	    create_source(latlng, name, sitecode, type, lat, long, siteid, i);
    bounds.extend(latlng);
       }
       map.fitBounds(bounds);
       locationSelect.style.visibility = "visible";
       locationSelect.onchange = function() {
         var markerNum = locationSelect.options[locationSelect.selectedIndex].value;
         google.maps.event.trigger(markers[markerNum], 'mouseover');
       };
      });
    }
  

 function searchLocationsNear2(center) {
     clearLocations();
	 option_num=0; 
    var radius = 300; //Radius for tracking
     var searchUrl = 'db_search.php?lat=' + center.lat() + '&lng=' + center.lng() + '&radius=' + radius;
     downloadUrl(searchUrl, function(data) {
       var xml2 = parseXml(data);
       var markerNodes = xml2.documentElement.getElementsByTagName("marker");
       var bounds = new google.maps.LatLngBounds();
       for (var i = 0; i < markerNodes.length; i++) {
         var name = markerNodes[i].getAttribute("name");
         var sitecode = markerNodes[i].getAttribute("sitecode");
var lat = markerNodes[i].getAttribute("lat");
var long = markerNodes[i].getAttribute("lng");
         var distance = parseFloat(markerNodes[i].getAttribute("distance"));
         var latlng = new google.maps.LatLng(
              parseFloat(markerNodes[i].getAttribute("lat")),
              parseFloat(markerNodes[i].getAttribute("lng")));var type = markerNodes[i].getAttribute("sitetype");
	var siteid = markerNodes[i].getAttribute("siteid");
	    create_source(latlng, name, sitecode, type, lat, long, siteid, i);
    bounds.extend(latlng);
       }
       map.fitBounds(bounds);
       locationSelect.style.visibility = "visible";
       locationSelect.onchange = function() {
         var markerNum = locationSelect.options[locationSelect.selectedIndex].value;
         google.maps.event.trigger(markers[markerNum], 'mouseover');
       };
      });
    }
  
   function createMarker(latlng, name, sitecode, type, lat, long, sourcename, sourcecode, sourcelink, siteid) 
   {
	   
//send out an ajax request to retrieve the picname

$.ajax({
  type: "POST",
  url: "getsitepic.php?sc="+siteid
}).done(function( msg ) {
  if(msg!=-1)
  {

 var html = "<div id='menu12' style='float:left;'><b>" + name + "</b> <br/>Site Type: "+type+"<br/>Latitude: "+parseFloat(lat).toFixed(4)+"<br/>Longitude: "+parseFloat(long).toFixed(4)+"<br/>Source: <a href='"+sourcelink+"' target='_blank'>"+sourcename+"</a><br/><a href='details.php?siteid="+siteid+"'>Click here for site details and data</a></div><div id='spic' style='margin-left:5px;height:100px;width:100px;float:left;'>"+msg+"</div>";

 var marker = new google.maps.Marker({
    map: map,
    position: latlng
  });
  google.maps.event.addListener(marker, 'mouseover', function() {
    infoWindow.setContent(html);
    infoWindow.open(map, marker);
  });
  markers.push(marker);

}
else
{

 //var html = "<div id='menu12' style='float:left;'><b>" + name + "</b> <br/>Site Type: "+type+"<br/>Latitude: "+lat+"<br/>Longitude: "+long+"<br/>Source: <a href='"+sourcelink+"' target='_blank'>"+sourcename+"</a><br/><a href='details.php?siteid="+siteid+"'>Click here for site details and data</a></div>";
 var html = "<div id='menu12' style='float:left;'><b>" + name + "</b> <br/><?php echo $SiteType; ?> "+type+"<br/><?php echo $Latitude; ?> "+parseFloat(lat).toFixed(4)+"<br/><?php echo $Longitude;?> "+parseFloat(long).toFixed(4)+"<br/>Source: <a href='"+sourcelink+"' target='_blank'>"+sourcename+"</a><br/><a href='details.php?siteid="+siteid+"'><?php echo $VisitSite; ?></a></div>";

 var marker = new google.maps.Marker({
    map: map,
    position: latlng
  });
  google.maps.event.addListener(marker, 'mouseover', function() {
    infoWindow.setContent(html);
    infoWindow.open(map, marker);
  });
  markers.push(marker);

}
});


	   

  
 
}


    function createOption(name, num, sourcename) {
		
      var option = document.createElement("option");
      option.value = option_num;
      option.innerHTML = name + " (Source : " + sourcename + ")";
      locationSelect.appendChild(option);
   option_num=option_num+1;
    }

    function downloadUrl(url, callback) {
      var request = window.ActiveXObject ?
          new ActiveXObject('Microsoft.XMLHTTP') :
          new XMLHttpRequest;

      request.onreadystatechange = function() {
        if (request.readyState == 4) {
          request.onreadystatechange = doNothing;
          callback(request.responseText, request.status);
        }
      };

      request.open('GET', url, true);
      request.send(null);
    }

    function parseXml(str) {
      if (window.ActiveXObject) {
        var doc = new ActiveXObject('Microsoft.XMLDOM');
        doc.loadXML(str);
        return doc;
      } else if (window.DOMParser) {
        return (new DOMParser).parseFromString(str, 'text/xml');
      }
    }

    function doNothing() {}

    //]]>
  </script>

</head>

<body background="images/bkgrdimage.jpg" onLoad="load()">

<table width="960" border="0" align="center" cellpadding="0" cellspacing="0">
  <tr>
    <td colspan="2"><?php include "topBanner.php" ; ?></td>
  </tr>
  <tr>
    <td colspan="2" align="right" valign="middle" bgcolor="#3c3c3c"><?php require_once 'header.php'; ?></td>
  </tr>
  <tr>
    <td width="240" valign="top" bgcolor="#f2e6d6"><?php echo "$nav"; ?> </td>
    <td width="720" valign="top" bgcolor="#FFFFFF"><blockquote><br />
         <table width="630" border="0">
           <tr>
             <td>&nbsp;</td>
           </tr>
         
           <tr>
             <td width="630" height="450"><div id="map" style="width:100%; height:100%"></div></td>
           </tr>
           <tr>
             <td>&nbsp;</td>
           </tr>
           <tr>
             <!--<td>To search for a data collection sites, simply type in the city or hit the button "Find sites near me!" to show sites within a 300 mile radius of your present geographic location. (Note: Sites in which there is no data will NOT be displayed below.)</td>-->
             <td><?php echo $ToSearch; ?></td>
           </tr>
            <tr>
             <td>&nbsp;</td>
           </tr>
            <tr>
             <!--<td><input type="text" id="addressInput" size="10"/>
               <select name="radiusSelect" id="radiusSelect">
                 <option value="25" selected>25mi</option>
                 <option value="50">50mi</option>
                 <option value="100">100mi</option>
                 <option value="200">200mi</option>
                 <option value="300">300mi</option>
                 <option value="400">400mi</option>
                 <option value="500">500mi</option>
               </select>
               <input type="button" onClick="searchLocations()" value="Search"/>
               <input type='button' onClick="loadall()" value="Reset Search"/>
             <input type='button' onClick="track_loc()" value="Find sites near me!"/></td>-->
             <td><input type="text" id="addressInput" size="10"/>
               <select name="radiusSelect" id="radiusSelect">
                 <option value="25" selected><?php echo $TwentyFive; ?></option>
                 <option value="50"><?php echo $Fifty; ?></option>
                 <option value="100"><?php echo $OneHundred; ?></option>
                 <option value="200"><?php echo $TwoHundred; ?></option>
                 <option value="300"><?php echo $ThreeHundred; ?></option>
                 <option value="400"><?php echo $FourHundred; ?></option>
                 <option value="500"><?php echo $FiveHundred; ?></option>
               </select>
               <input type="button" onClick="searchLocations()" value="<?php echo $Search;?>"/>
               <input type='button' onClick="loadall()" value="<?php echo $ResetSearch;?>"/>
             <input type='button' onClick="track_loc()" value="<?php echo $FindSites; ?>"/></td>
           </tr>
           
           <tr>
             <td>&nbsp;</td>
           </tr>
             <tr>
             <td><div><select name="locationSelect" id="locationSelect" style="width:100%;"></select></div></td>
           </tr>
           
           
         </table>
    </blockquote>
      	 <p>&nbsp;</p>
    </td>
  </tr>
  <tr>
    <script src="js/footer.js"></script>
  </tr>  
</table>
</body>
</html>