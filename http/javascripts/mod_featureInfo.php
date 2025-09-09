<?php
# $Id: mod_featureInfo.php 10271 2019-09-27 06:51:45Z armin11 $
# http://www.mapbender.org/index.php/mod_featureInfo.php
# Copyright (C) 2002 CCGIS
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2, or (at your option)
# any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.

require_once(dirname(__FILE__)."/../php/mb_validateSession.php");
include '../include/dyn_js.php';
//defaults for element vars
?>
// <script>

var ignoreWms = typeof ignoreWms === "undefined" ? [] : ignoreWms;

if(typeof(featureInfoLayerPopup)==='undefined')
        var featureInfoLayerPopup = 'false';
if(typeof(featureInfoPopupHeight)==='undefined')
        var featureInfoPopupHeight = '200';
if(typeof(featureInfoPopupWidth)==='undefined')
        var featureInfoPopupWidth = '270';
if(typeof(featureInfoPopupPosition)==='undefined')
        var featureInfoPopupPosition = 'center';
if(typeof(dialogPosition)==='undefined')
        var dialogPosition = 'center';

if(typeof(reverseInfo)==='undefined' || reverseInfo === 'false')
    var reverseInfo = false;
if(typeof(featureInfoLayerPreselect)==='undefined' || featureInfoLayerPreselect === 'true'){
        var featureInfoLayerPreselect = true;
} else {
        var featureInfoLayerPreselect = false;
}
if(typeof(featureInfoDrawClick)==='undefined' || featureInfoDrawClick === 'true'){
        var featureInfoDrawClick = true;
} else {
        var featureInfoDrawClick = false;
}
if(typeof(featureInfoCircleColor)==='undefined')
        var featureInfoCircleColor = '#ff0000';
if(typeof(featureInfoCollectLayers)==='undefined' || featureInfoCollectLayers === 'false')
        var featureInfoCollectLayers = false;
if (typeof(featureInfoShowKmlTreeInfo) === 'undefined' || featureInfoShowKmlTreeInfo === 'false')
        var featureInfoShowKmlTreeInfo = false;
if (featureInfoPrint === undefined || featureInfoPrint === 'false') {
  var featureInfoPrint = false;
}
if (featureInfoPrintConfig === undefined) {
  var featureInfoPrintConfig = '../print/Dummy_A4.json';
}
if (featureInfoPrintButton === undefined) {
  var featureInfoPrintButton = '#printPDF';
}
if (featureInfoShowAlert === undefined) {
  var featureInfoShowAlert = 0; //0:noAlert,1:alert,2:divAlert,3:divAlertTransparent
}

var mod_featureInfo_elName = "<?php echo $e_id;?>";
var mod_featureInfo_frameName = "";
var mod_featureInfo_target = "<?php echo $e_target[0]; ?>";
var mod_featureInfo_mapObj = null;

var mod_featureInfo_img_on = new Image(); mod_featureInfo_img_on.src =  "<?php  echo preg_replace("/_off/","_on",$e_src);  ?>";
var mod_featureInfo_img_off = new Image(); mod_featureInfo_img_off.src ="<?php  echo $e_src;  ?>";
var mod_featureInfo_img_over = new Image(); mod_featureInfo_img_over.src = "<?php  echo preg_replace("/_off/","_over",$e_src);  ?>";

if (featureInfoDrawClick) {
        var standingHighlightFeatureInfo = null;
        Mapbender.events.afterMapRequest.register( function(){
                if(standingHighlightFeatureInfo){
                        standingHighlightFeatureInfo.paint();
                }
        });
}

eventInit.register(function () {
        mb_regButton(function init_featureInfo1(ind){
                mod_featureInfo_mapObj = getMapObjByName(mod_featureInfo_target);
                mb_button[ind] = document.getElementById(mod_featureInfo_elName);
                mb_button[ind].img_over = mod_featureInfo_img_over.src;
                mb_button[ind].img_on = mod_featureInfo_img_on.src;
                mb_button[ind].img_off = mod_featureInfo_img_off.src;
                mb_button[ind].status = 0;
                mb_button[ind].elName = mod_featureInfo_elName;
                mb_button[ind].fName = mod_featureInfo_frameName;
                mb_button[ind].go = function () {
                        mod_featureInfo_click();
                };
                mb_button[ind].stop = function () {
                        mod_featureInfo_disable();
                };
        });
});

function showAlert(variant = 'default', timeoutDuration = 5000) {
    // Überprüfen, ob das Div bereits existiert
    if (document.getElementById('alertDiv') || document.getElementById('alertDivTransparent')) {
        return;
    } 
    
    const alertDiv = document.createElement('div');
    
    if (variant === 'default') {
        alertDiv.id = 'alertDiv';
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '90px';
        alertDiv.style.right = '90px';
        alertDiv.style.backgroundColor = 'rgba(213, 31, 40, 0.8)';
        alertDiv.style.color = 'white';
        alertDiv.style.padding = '.5em 1em';
        alertDiv.style.maxWidth = '200px';
        alertDiv.style.zIndex = '1000';
        alertDiv.style.fontFamily = 'Helvetica, Arial, sans-serif';
        alertDiv.style.fontSize = '1.1em';
        alertDiv.style.letterSpacing = '1px';
        alertDiv.innerHTML = '<p style="margin: 0px;text-align: center;">Bitte abfragbare Themen ausgewählen</p><span id="closeBtn" style="cursor: pointer;position:absolute;top:0px;right:5px;display:none;">&times;</span>';

        // Hinzufügen des Divs zum Body
        document.body.appendChild(alertDiv);

        // Event-Listener für das Schließen des Divs
        document.getElementById('closeBtn').addEventListener('click', function() {
            document.body.removeChild(alertDiv);
        });

        // Timeout zum automatischen entfernen des Divs
        let timeout = setTimeout(function() {
            if (document.body.contains(alertDiv)) {
                document.body.removeChild(alertDiv);
            }           
        }, timeoutDuration);

        // Funktion zum Abbrechen des Timeouts und Anzeigen des Schließen-Symbols
        function clearTimeoutOnMouseEnter() {
            clearTimeout(timeout);
            document.getElementById('closeBtn').style.display = 'block';
        }
        alertDiv.addEventListener('mouseenter', clearTimeoutOnMouseEnter);

    } else if (variant === 'transparent') {
        alertDiv.id = 'alertDivTransparent';
        alertDiv.style.position = 'fixed';
        alertDiv.style.top = '1px';
        alertDiv.style.left = '3px';
        alertDiv.style.width = '113px';
        alertDiv.style.height = '46px';
        alertDiv.style.border = '2px dashed red';
        alertDiv.style.backgroundColor = 'transparent';
        alertDiv.style.zIndex = '1000';
    
    // Hinzufügen des Divs zum Body
    document.body.appendChild(alertDiv);
    
    // Timeout zum automatischen Ausblenden des Divs
    let timeout = setTimeout(function() {
        if (document.body.contains(alertDiv)) {
            document.body.removeChild(alertDiv);
        }
    }, timeoutDuration);
    }
}


/**
 * some things from http://stackoverflow.com/a/10997390/11236
 * function changes the order of cs-values for a given get parameter
 */
function changeURLValueOrder(url, param){
    var newAdditionalURL = "";
    var tempArray = url.split("?");
    var baseURL = tempArray[0];
    var additionalURL = tempArray[1];
    var temp = "";
    if (additionalURL) {
        tempArray = additionalURL.split("&");
        for (i=0; i<tempArray.length; i++){
                if(tempArray[i].split('=')[0] != param){
                        newAdditionalURL += temp + tempArray[i];
                        temp = "&";
                } else {
                        //get value and sort it in other direction
                        var oldValue = tempArray[i].split('=')[1];
                        var oldValueArray = oldValue.split(",");
                        var newValue = '';
                        for (var j = 0; j < oldValueArray.length; j++) {
                                newValue = newValue+oldValueArray[oldValueArray.length - (j+1)]+',';
                        }
                        newValue = newValue.replace(/,+$/,'');
                }
        }
    }
    var rows_txt = temp + "" + param + "=" + newValue;
    return baseURL + "?" + newAdditionalURL + rows_txt;
}

function mod_featureInfo_click(){
        var el = mod_featureInfo_mapObj.getDomElement();

        if (el) {
                $(el).bind("click", mod_featureInfo_event)
                        .css("cursor", "help");
        }
}
function mod_featureInfo_disable(){
        var el = mod_featureInfo_mapObj.getDomElement();

        if (el) {
                $(el).unbind("click", mod_featureInfo_event)
                        .css("cursor", "default");
                $("#featureInfo1").removeClass("myOnClass");
        }
}

function makeDialog($content, title, dialogPosition, offset, printInfo) {
    dialogPosition = dialogPosition || featureInfoPopupPosition;
    if(featureInfoPopupPosition.length === 2 && !isNaN(featureInfoPopupPosition[0]) && !isNaN(featureInfoPopupPosition[1])) {
        offset = offset || 0;
        var dialogPosition = [];
        dialogPosition[0] = featureInfoPopupPosition[0] + offset;
        dialogPosition[1] = featureInfoPopupPosition[1] + offset;
    }
    var uniqueClass = 'feature-info-dialog';
    // Dialog zerstören und entfernen, falls schon vorhanden
    var dialogs = document.querySelectorAll('.' + uniqueClass);
    dialogs.forEach(function(dialog) {
        //console.log('Removing dialog with uniqueClass:', dialog);
        if (dialog.parentNode) {
            dialog.parentNode.removeChild(dialog);
        }
    });
    var dialogConfig = {
      bgiframe: true,
      autoOpen: true,
      modal: false,
      title: title,
      width: parseInt(featureInfoPopupWidth, 10),
      height: parseInt(featureInfoPopupHeight, 10),
      position: dialogPosition,
      dialogClass: uniqueClass,
      buttons: {
        "Ok": function() {
          if (standingHighlightFeatureInfo !== null) {
            standingHighlightFeatureInfo.clean();
          }
          $(this).dialog('close').dialog('destroy').remove();
        }
      },
      close: function(){
          if (standingHighlightFeatureInfo !== null) {
            standingHighlightFeatureInfo.clean();
	  }
	  $(this).dialog('destroy').remove();
      },
      open: function(){
          $('#tree2Container').hide() && $('a.toggleLayerTree').removeClass('activeToggle'),
          $('#toolsContainer').hide() && $('a.toggleToolsContainer').removeClass('activeToggle');
      }
    };
    if (featureInfoPrint) {
      dialogConfig.buttons['Print'] = function () {
        $(featureInfoPrintButton).data('printObj').printFeatureInfo(printInfo, $content)
      }
    }
    var dialogElement = $content.dialog(dialogConfig).parent().css({ position:"fixed" });

    // Overlay-Div erstellen und hinzufügen
    var overlay = $('<div class="dialog-overlay"></div>').css({
        position: 'absolute',
        top: 0,
        left: 0,
        width: '100%',
        height: '100%',
        background: 'transparent',
        zIndex: 9999,
        display: 'none'
    }).appendTo(dialogElement);

    return dialogElement;
}

function featureInfoDialog(featureInfo, dialogPosition, offset, printInfo) {
    var title = "<?php echo _mb("Information");?>";
    if (printInfo !== undefined) {
        printInfo = $.extend({}, printInfo, {
          urls: [featureInfo]
        });
    }
    var $iframe = $("<iframe>")
            .attr("frameborder", 0)
            .attr("width", "100%")
            .attr("id", "featureInfo")
            .attr("title", title)
            .attr("style", "height:calc(100% - 3px);")
            .attr("src", featureInfo.request);
    return makeDialog($("<div>").append($iframe), title, dialogPosition, offset, printInfo);
}

function ownDataDialog(ownData, dialogPosition, offset, printInfo) {
    if (printInfo !== undefined) {
      console.error('kml data is not printable');
      printInfo = undefined
    }
    var $box = $('<div>').html(ownData.content);
    return makeDialog($box,
            "<?php echo _mb("Information");?>", dialogPosition, offset, printInfo);
}

function featureInfoWindow(featureInfo) {
    return window.open(featureInfo.request, "" , "width="+featureInfoPopupWidth+",height="+featureInfoPopupHeight+",scrollbars=yes,resizable=yes");
}

function ownDataWindow(ownData) {
    var w = featureInfoWindow("");
    var $body = $(w.document.body);
    $body.html(ownData.content);
}

function makeListLine(url, title, legendurls, onclick) {
    var $row = $("<tr>");
    var $title = $("<td>")
        .attr("valign", "top")
        .appendTo($row);
    var $link = $("<a>")
        .css("text-decoration", "underline")
        .attr("href", url)
        .attr("target", "_blank")
        .html(title)
        .appendTo($title);
    if (onclick) {
        $link.bind('click', onclick);
    }
    var $legend = $("<td>")
        .appendTo($row);
    if (legendurls.length === 0) {
        legendurls = [""];
    }
    legendurls.forEach(function (legendurl) {
        $("<img>")
            .attr("src", legendurl)
            .attr("alt", "<?php echo _mb("No legend available");?>")
            .appendTo($legend);
        $("<br/>")
            .appendTo($legend);
    });

    return $row;
}

function makeFeatureInfoListLine(url, title, legendurls) {
    return makeListLine(url, title, legendurls)
}

function makeOwnDataListLine(ownData) {
    return makeListLine("#", ownData.title, [], function (e) {
        ownDataWindow(ownData)
        e.preventDefault();
    });
}

function featureInfoListDialog(urls, ownDataInfos, printInfo) {
    var $featureInfoList = $("<table>")
            .attr("border", 1);

    if (reverseInfo) {
        urls.reverse();
        ownDataInfos.reverse();

        ownDataInfos.forEach(function (ownDataInfo) {
            $featureInfoList.append(makeOwnDataListLine(ownDataInfo));
        });
    }

    for(var i=0; i < urls.length; i++){
        var $line;
        if (featureInfoCollectLayers) {
            $line = makeFeatureInfoListLine(urls[i].request, urls[i].title, urls[i].legendurl.split(","));
        } else {
            if (urls[i].inBbox) {
                if (urls[i].legendurl !== "empty" ) {
                    $line = makeFeatureInfoListLine(urls[i].request, urls[i].title, [urls[i].legendurl]);
                } else {
                    $line = makeFeatureInfoListLine(urls[i].request, urls[i].title, [""]);
                }
            }
        }
        if ($line) {
            $featureInfoList.append($line);
        }
    }

    if (!reverseInfo) {
        ownDataInfos.forEach(function (ownDataInfo) {
            $featureInfoList.append(makeOwnDataListLine(ownDataInfo));
        });
    }

    if (printInfo !== undefined) {
        printInfo = $.extend({}, printInfo, {
          urls: urls
        });
    }

    makeDialog($("<div id='featureInfo_preselect'></div>").append($featureInfoList),
        "<?php echo _mb("Please choose a requestable Layer");?>", undefined, undefined, printInfo);
}
var featureInfoEnabled = true; // Variable to track the status of featureInfo click event

Mapbender.enableFeatureInfo = function() {
    featureInfoEnabled = true;
}

Mapbender.disableFeatureInfo = function() {
    featureInfoEnabled = false;
}

var isDragging = false;

//function mod_featureInfo_event(e){
window.mod_featureInfo_event = function(e) {
if (!isDragging && featureInfoEnabled) {
    var featureInfos;
    var point = mod_featureInfo_mapObj.getMousePosition(e);
    //calculate realworld position
    var realWorldPoint = Mapbender.modules[options.target].convertPixelToReal(point);
    var ownDataInfos = [];
    var printInfo;
    var drawCircle = true;
    
    if (featureInfoPrint) {
        printInfo = {
            config: featureInfoPrintConfig,
            point: realWorldPoint
        };
    }
    if (featureInfoShowKmlTreeInfo) {
        if (Mapbender.modules.kmlTree === undefined) {
            console.error('kmltree module is needed if element_var \'featureInfoShowKmlTreeInfo\' is set to true')
        }
        var kmlTree = Mapbender.modules.kmlTree;
        ownDataInfos = kmlTree.getFeatureInfos(e);
    }
        if (featureInfoDrawClick) {
            var map = Mapbender.modules[options.target];
            if (standingHighlightFeatureInfo !== null) {
                standingHighlightFeatureInfo.clean();
            } else {
                standingHighlightFeatureInfo = new Highlight(
                    [options.target],
                    "standingHighlightFeatureInfo",
                    {"position":"absolute", "top":"0px", "left":"0px", "z-index":100},
                    2);
            }
            //get coordinates from point
            var ga = new GeometryArray();
            //TODO set current epsg!
            var srs = Mapbender.modules[options.target].getSRS();
            ga.importPoint({
                coordinates:[realWorldPoint.x,realWorldPoint.y,null]
            }, srs)
            var m = ga.get(-1,-1);
            standingHighlightFeatureInfo.add(m, featureInfoCircleColor);
            standingHighlightFeatureInfo.paint();
            map.setMapRequest();
        }

        eventBeforeFeatureInfo.trigger({ "fName": mod_featureInfo_target });
        if(document.getElementById("FeatureInfoRedirect")){
            //TODO this code should go to featureInfo Redirect module
            //FIXME this does not work for multiple urls
            //FIXME this does not work for kmlTree
            //fill the frames
            for(var i=0; i < mod_featureInfo_mapObj.wms.length; i++){
                var req = mod_featureInfo_mapObj.wms[i].getFeatureInfoRequest(mod_featureInfo_mapObj, point);
                if(req)
                    window.frames.FeatureInfoRedirect.document.getElementById(mod_featureInfo_mapObj.wms[i].wms_id).src = req;
            }
        } else {
            //maybe someone will show all selectable layers in a window before
            if (featureInfoLayerPreselect) {
                $("#featureInfo_preselect").remove();
                //build list of possible featureInfo requests
                featureInfos = mod_featureInfo_mapObj.getFeatureInfoRequestsForLayers(point, ignoreWms, Mapbender.modules[options.target].getSRS(), realWorldPoint, featureInfoCollectLayers) || [];
                var length = featureInfos.length + ownDataInfos.length;
                if (length === 0) {
                    if (standingHighlightFeatureInfo !== null) {
                        standingHighlightFeatureInfo.clean();
                    }
                    switch (featureInfoShowAlert) {
                        case 0:
                            break;
                        case 1:
                            alert(unescape("Please select a layer! \n Bitte waehlen Sie eine Ebene zur Abfrage aus!"));
                            break;
                        case 2:
                            showAlert('default', 5000);
                            break;
                        case 3:
                            showAlert('transparent', 1000);
                            break;
                    }
                    return false;
                }
                if (length === 1) {
                    //dont show interims window!
                    //open featureInfo directly
                    if (featureInfoLayerPopup){
                        if (featureInfos.length === 1) {
                            featureInfoDialog(featureInfos[0], undefined, undefined, printInfo);
                        
                        } else {
                            ownDataDialog(ownDataInfos[0], undefined, undefined, printInfo);
                        }
                        return false;
                        
                        } else {
                            if (featureInfos.length === 1) {
                                featureInfoWindow(featureInfos[0]);
                        } else {
                            ownDataWindow(ownDataInfos[0]);
                        }
                        return false;
                        }
                    }
                    featureInfoListDialog(featureInfos, ownDataInfos, printInfo);
                } else {
                    featureInfos = mod_featureInfo_mapObj.getFeatureInfoRequests(point, ignoreWms) || [];
                    var length = featureInfos.length + ownDataInfos.length;
                    if (length > 0){
                        for (var i=0; i < featureInfos.length; i++){
                            //TODO: also rewind the LAYERS parameter for a single WMS FeatureInfo REQUEST if needed?
                            if (reverseInfo) {
                                if (typeof(featureInfos[i]) !== "undefined") {
                                    featureInfos[i] = changeURLValueOrder(featureInfos[i], 'LAYERS');
                                }
                            }
                            if(featureInfoLayerPopup){
                                featureInfoDialog(featureInfos[i], dialogPosition, i * 25, undefined, printInfo);
                            } else {
                                featureInfoWindow(featureInfos[i]);
                            }
                        }
                    
                        for(var i=0; i < ownDataInfos.length; i++){
                            if(featureInfoLayerPopup === 'true'){
                                ownDataDialog(ownDataInfos[i], dialogPosition, (featureInfos.length + i) * 25, printInfo);
                            } else {
                                ownDataWindow(ownDataInfos[i]);
                            }
                        }
                    } else {
                        if (standingHighlightFeatureInfo !== null) {
                            standingHighlightFeatureInfo.clean();
                        }
                        switch (featureInfoShowAlert) {
                            case 0:
                                break;
                            case 1:
                                alert(unescape("Please select a layer! \n Bitte waehlen Sie eine Ebene zur Abfrage aus!"));
                                break;
                            case 2:
                                showAlert('default', 5000);
                                break;
                            case 3:
                                showAlert('transparent', 1000);
                                break;
                        }
                    }
                }
                setFeatureInfoRequest(mod_featureInfo_target, point.x, point.y);
            }
        }
}
