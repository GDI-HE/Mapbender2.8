[1mdiff --git a/http/plugins/mb_print.php b/http/plugins/mb_print.php[m
[1mindex bd5cb60c..de8b87c9 100644[m
[1m--- a/http/plugins/mb_print.php[m
[1m+++ b/http/plugins/mb_print.php[m
[36m@@ -1242,10 +1242,10 @@[m [mvar PrintPDF = function (options) {[m
     var bbox;[m
     var srs = map.getSrs ? map.getSrs() : '';[m
     if (srs && srs.toUpperCase() === 'EPSG:4326') {[m
[31m-      // geographic SRS: convert real-world metres to degrees[m
[32m+[m[32m      // geographic SRS: convert real-world metres to degrees using N-S scale only.[m
[32m+[m[32m      // No cos(lat) so bbox aspect ratio matches paper orientation.[m
       var degPerMeter = 360.0 / (2.0 * Math.PI * 6378137.0);[m
[31m-      var lat = printInfo.point.y * Math.PI / 180.0;[m
[31m-      var halfW = rawScale * getPDFMapSize("width")  / 1000.0 * degPerMeter / Math.cos(lat);[m
[32m+[m[32m      var halfW = rawScale * getPDFMapSize("width")  / 1000.0 * degPerMeter;[m
       var halfH = rawScale * getPDFMapSize("height") / 1000.0 * degPerMeter;[m
       bbox = [[m
         printInfo.point.x - 0.5 * halfW,[m
[1mdiff --git a/lib/printbox.js b/lib/printbox.js[m
[1mindex 7cb9407f..9111ca1b 100644[m
[1m--- a/lib/printbox.js[m
[1m+++ b/lib/printbox.js[m
[36m@@ -408,9 +408,10 @@[m [mMapbender.PrintBox = function (options) {[m
 		xtentx = coordsArray[2] - coordsArray[0];[m
 		var xtentxInM = xtentx;[m
 		if (map.epsg === 'EPSG:4326') {[m
[31m-			var centerLat = (parseFloat(coordsArray[1]) + parseFloat(coordsArray[3])) / 2;[m
[31m-			var latRad = centerLat * Math.PI / 180.0;[m
[31m-			xtentxInM = xtentx * (2.0 * Math.PI * 6378137.0 / 360.0) * Math.cos(latRad);[m
[32m+[m			[32m// Convert degrees to metres using the N-S (latitude) scale only.[m
[32m+[m			[32m// No cos(lat) here so that setScale/getScale stay consistent and[m
[32m+[m			[32m// the bbox aspect ratio matches the paper (portrait stays portrait).[m
[32m+[m			[32mxtentxInM = xtentx * (2.0 * Math.PI * 6378137.0 / 360.0);[m
 		}[m
 		scale = parseInt(Math.round(xtentxInM / (printWidth / 100)), 10);[m
 		return scale;[m
[36m@@ -476,8 +477,9 @@[m [mMapbender.PrintBox = function (options) {[m
 			var halfH = realHeightInM;[m
 			if (map.epsg === 'EPSG:4326') {[m
 				var degPerMeter = 360.0 / (2.0 * Math.PI * 6378137.0);[m
[31m-				var latRad = centerMap.y * Math.PI / 180.0;[m
[31m-				halfW = realWidthInM * degPerMeter / Math.cos(latRad);[m
[32m+[m				[32m// Use the same N-S scale for both axes so the bbox aspect ratio[m
[32m+[m				[32m// matches the paper (portrait stays portrait on screen).[m
[32m+[m				[32mhalfW = realWidthInM * degPerMeter;[m
 				halfH = realHeightInM * degPerMeter;[m
 			}[m
 [m
