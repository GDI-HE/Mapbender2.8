//<script>
/**
 * Package: printPDF
 *
 * Description:
 * Mapbender print PDF with PDF templates module.
 *
 * Files:
 *  - http/plugins/mb_print.php
 *  - http/print/classes
 *  - http/print/printFactory.php
 *  - http/print/printPDF_download.php
 *  - lib/printbox.js
 *
 * SQL:
 * > INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment,
 * > e_title, e_element, e_src, e_attributes, e_left, e_top, e_width,
 * > e_height, e_z_index, e_more_styles, e_content, e_closetag, e_js_file,
 * > e_mb_mod, e_target, e_requires, e_url) VALUES('<appId>','printPDF',
 * > 2,1,'pdf print','Print','div','','',1,1,2,2,5,'',
 * > '<div id="printPDF_working_bg"></div><div id="printPDF_working"><img src="../img/indicator_wheel.gif" style="padding:10px 0 0 10px">Generating PDF</div><div id="printPDF_input"><form id="printPDF_form" action="../print/printFactory.php"><div id="printPDF_selector"></div><div class="print_option"><input type="hidden" id="map_url" name="map_url" value=""/><input type="hidden" id="legend_url" name="legend_url" value=""/><input type="hidden" id="overview_url" name="overview_url" value=""/><input type="hidden" id="map_scale" name="map_scale" value=""/><input type="hidden" name="measured_x_values" /><input type="hidden" name="measured_y_values" /><input type="hidden" name="map_svg_kml" /><input type="hidden" name="svg_extent" /><input type="hidden" name="map_svg_measures" /><br /></div><div class="print_option" id="printPDF_formsubmit"><input id="submit" type="submit" value="Print"><br /></div></form><div id="printPDF_result"></div></div>',
 * > 'div','../plugins/mb_print.php',
 * > '../../lib/printbox.js,../extensions/jquery-ui-1.7.2.custom/development-bundle/external/bgiframe/jquery.bgiframe.js,../extensions/jquery.form.min.js',
 * > 'mapframe1','','http://www.mapbender.org/index.php/Print');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES('<appId>', 'printPDF',
 * > 'mbPrintConfig', '{"Standard": "mapbender_template.json"}', '' ,'var');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES('<appId>', 'printPDF',
 * > 'unlink', 'true', 'delete print pngs after pdf creation' ,'php_var');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES('<appId>', 'printPDF',
 * > 'logRequests', 'false', 'log wms requests for debugging' ,'php_var');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES('<appId>', 'printPDF',
 * > 'logType', 'file', 'log mode can be set to file or db' ,'php_var');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES('<appId>', 'printPDF',
 * > 'timeout', '90000', 'define maximum milliseconds to wait for print request finished' ,'var');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES('<appId>', 'body',
 * > 'print_css', '../css/print_div.css', '' ,'file/css');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES('<appId>', 'printPDF',
 * > 'legendColumns', '2', 'define number of columns on legendpage' ,'php_var');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES('<appId>', 'printPDF',
 * > 'printLegend', 'true', 'define whether the legend should be printed or not' ,'php_var');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES('<appId>', 'printPDF',
 * > 'secureProtocol', 'true', 'define if https should be used even if the server don''t
 * > know anything about the requested protocol' ,'php_var');
 * >
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name,
 * > var_value, context, var_type) VALUES('<appId>', 'printPDF',
 * > 'reverseLegend', 'true', 'Define if order of legend should be reversed' ,'var');
 *
 * Help:
 * http://www.mapbender.org/PrintPDF_with_template
 *
 * Maintainer:
 * http://www.mapbender.org/User:Michael_Schulz
 * http://www.mapbender.org/User:Christoph_Baudson
 *
 * Parameters:
 * mbPrintConfig      - *[optional]* object with name and filename of template,
 * 							like 	{
 * 										"Standard": "a_template.json",
 * 										"Different": "another_template.json"
 * 									}
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

var myTarget = options.target ? options.target[0] : "mapframe1";
var myId = options ? options.id : "printPDF";

var mbPrintConfig = options.mbPrintConfig;
//wms_ids of services where legends should not be printed
var exclude = typeof options.exclude === "undefined" ? [] : options.exclude;
/* the array of json print config files */

if (typeof mbPrintConfig === "object") {
  mbPrintConfigFilenames = [];
  mbPrintConfigTitles = [];
  for (var i in mbPrintConfig) {
    mbPrintConfigFilenames.push(mbPrintConfig[i]);
    mbPrintConfigTitles.push(i);
  }
}
if (typeof mbPrintConfigFilenames === "undefined") {
  mbPrintConfigFilenames = ["mapbender_template.json"];
}

if (typeof mbPrintConfigTitles === "undefined") {
  mbPrintConfigTitles = ["Default"];
}


var mbPrintConfigPath = "../print/";


/* ------------- printbox addition ------------- */

var PrintPDF = function (options) {

  var that = this;

  /**
   * Property: actualConfig
   *
   * object, holds the actual configuration after loading the json file
   */
  var actualConfig;

  /**
   * Callback set by printFeatureInfo to intercept AJAX errors (timeout, server error).
   * When set, the hookForm error handler calls this instead of showing the generic alert.
   */
  var pfiErrorCallback = null;

  /**
   * constructor
   */
  eventInit.register(function () {
    mod_printPDF_init();
  });

  /**
   * Property: printBox
   *
   * the movable printframe
   */
  var printBox = null;

  eventAfterMapRequest.register(function () {
    if (printBox !== null) {
      if (printFeatureInfoData !== null && pfiPixelCenter !== null) {
        // FeatureInfo mode: box stays at fixed pixel position for both pan and zoom.
        // Scale is derived from the current map zoom level so that:
        //   - Pan: map scale unchanged → same scale → box same pixel size
        //   - Zoom: map scale changes → scale updates → box stays same pixel size
        var nc = makeClickPos2RealWorldPos(myTarget, pfiPixelCenter[0], pfiPixelCenter[1]);
        var rawMapScale = getMapObjByName(myTarget).getScale();
        var magnitude = Math.pow(10, Math.floor(Math.log(rawMapScale) / Math.LN10));
        var newScale = Math.round(rawMapScale / magnitude) * magnitude;
        if (newScale > 0) {
          printBox.setCenterMap({x: nc[0], y: nc[1]});
          printBox.setScale(newScale);
          $('#printPDF_form #scale').val(newScale);
          $('#pfi_scale').val(newScale);
        }
      } else {
        printBox.repaint();
        if (!printBox.isVisible()) {
          $("#printPDF_form #scale").val("");
          $("#printPDF_form #coordinates").val("");
          $("#printPDF_form #angle").val("");
        }
      }
    }
  });
  /**
   * Method: createPrintBox
   *
   * creates a printBox in the current view, calculates the scale
   * (tbd. if not set from the config) so that the printbox fits in the mapframe.
   * Width and height are taken from the configuration.
   */
  this.createPrintBox = function (fixedPosition) {
    size = "A4";
    //document.form1.size.value = size;
    format = "portrait";
    var w, h;
    //validate();
    var map = getMapObjByName(myTarget);
    var map_el = map.getDomElement();
    var jqForm = $("#" + myId + "_form");
    var $scaleInput = $("#scale");

    if (printBox !== null) {
      destroyPrintBox();
      jqForm[0].scale.value = "";
      jqForm[0].coordinates.value = "";
      jqForm[0].angle.value = "";
    } else {
      var options = {
        target: myTarget,
        printWidth: getPDFMapSize("width") / 10,
        printHeight: getPDFMapSize("height") / 10,
        scale: $scaleInput.size() > 0 && !isNaN(parseInt($scaleInput.val(), 10)) ?
          parseInt($scaleInput.val(), 10) :
          Math.pow(10, Math.floor(Math.log(map.getScale()) / Math.LN10)),
        afterChangeAngle: function (obj) {
          if (typeof (obj) == "object") {
            if (typeof (obj.angle) == "number") {
              if (typeof (jqForm[0].angle) != "undefined") {
                jqForm[0].angle.value = obj.angle;
              }
            }
            if (obj.coordinates) {
              if (typeof (jqForm[0].coordinates) != "undefined") {
                jqForm[0].coordinates.value = String(obj.coordinates);
              }
            }
          }
        },
        afterChangeSize: function (obj) {
          if (typeof (obj) == "object") {
            if (obj.scale) {
              if ($("#scale").is("input")) {
                jqForm[0].scale.value = parseInt(obj.scale, 10);
              } else {
                //$("#scale .addedScale").remove();
                //$("#scale").append("<option selected class='addedScale' value='"+parseInt(obj.scale / 10, 10) * 10+"'>1 : " + parseInt(obj.scale / 10, 10) * 10 + "</option>");

                var currentScale = parseInt($("#scale").val(), 10);
                var objScale = parseInt(obj.scale / 10, 10) * 10;

                if (obj.scale != currentScale) {
                  var scaleOptions = [];
                  $("#scale option").each(function () {
                    scaleOptions.push(parseInt(this.value, 10));
                  });

                  var closest = getClosestNum(objScale, scaleOptions);
                  $("#scale option[value='" + closest + "']").attr('selected', 'selected');
                  if (printBox) {
                    if (objScale != closest) {
                      printBox.setScale(closest);
                    }
                  }
                }


              }
            }
            if (obj.coordinates) {
              if (typeof (jqForm[0].coordinates) != "undefined") {
                jqForm[0].coordinates.value = String(obj.coordinates);
              }
            }
          }
        }
      };
      if (fixedPosition) {
        $.extend(options, {
          realCenter: fixedPosition,
          fixed: true,
          pointColour: 'transparent',
          circleColour: 'transparent'
        });
      }
      printBox = new Mapbender.PrintBox(options);
      printBox.paintPoints();
      printBox.paintBox();
      printBox.show();
    }
  };
  
  function array_contains(hay,needle){
	    for(var i = 0; i < hay.length; i++ ){
	        if (hay[i] == needle){
	            return true
	        }
	    } 
	    return false;
  }
	
  function getClosestNum (num, ar) {
    var i = 0, closest, closestDiff, currentDiff;
    if (ar.length) {
      closest = ar[0];
      for (i; i < ar.length; i++) {
        closestDiff = Math.abs(num - closest);
        currentDiff = Math.abs(num - ar[i]);
        if (currentDiff < closestDiff) {
          closest = ar[i];
        }
        closestDiff = null;
        currentDiff = null;
      }
      //returns first element that is closest to number
      return closest;
    }
    //no length
    return false;
  }

  /**
   * Method: getPDFMapSize
   *
   * checks the actual config for the size w/h values.
   *
   * Parameters:
   * key      - string, the key which value to retrieve (currently width or height)
   */
  var getPDFMapSize = function (key) {
    for (var page in actualConfig.pages) {
      for (var pageElement in actualConfig.pages[page].elements) {
        if (actualConfig.pages[page].elements[pageElement].type == "map") {
          return actualConfig.pages[page].elements[pageElement][key];
        }
      }
    }
  };

  /**
   * Method: destroyPrintBox
   *
   * removes an existing printBox.
   */
  var destroyPrintBox = function () {
    if (printBox) {
      printBox.destroy();
      printBox = null;
      $("#printboxScale").val("");
      $("#printboxCoordinates").val("");
      $("#printboxAngle").val("");
    }
  };

  /**
   * Change status of printbox
   *
   * @param {String} newStatus either "hide or "show"
   */
  var showHidePrintBox = function (newStatus) {
    if (newStatus == "hide") {
      printBox.hide();
    } else {
      printBox.show();
    }
  };

  /**
   * Method: mod_printPDF_init
   *
   * initializes the print modules, generates template chooser and loads first configuration.
   */
  var mod_printPDF_init = function () {
    /* first we'd need to build the configuration selection */
    buildConfigSelector();
    /* second we'd need to read the json configuration */
    that.loadConfig(mbPrintConfigFilenames[0]);
    /* than we need the translation of the print button */
    $("#submit").val("<?php echo htmlentities(_mb("print"), ENT_QUOTES, "UTF-8");?>");

    //show printBox for first entry in printTemplate selectbox
    $("." + myId + "-dialog").bind("dialogopen", function () {
      printObj.createPrintBox();
    });

    //destroy printBox if printDialog is closed
    $("." + myId + "-dialog").bind("dialogclose", function () {
      destroyPrintBox();
    });
  };

  /**
   * Method: loadConfig
   *
   * GETs the config, build corresponding form, remove an existing printBox
   */
  this.loadConfig = function (configFilename, callback) {
    // the dataType to $.get is given explicitely, because there were instances of Mapbender that were returning
    // either json or a string, which trips up $.parseJSON which was being used in the callback
    $.get(mbPrintConfigPath + configFilename, function (json, status) {
      actualConfig = json;
      buildForm();
      hookForm();
      if (typeof callback === "function") {
        printBox = null;
        callback();
      }
    }, "json");
    destroyPrintBox();

  };

  /**
   * Method: hookForm
   *
   * utility method to connect the form plugin to the print form.
   */
  var hookForm = function () {
    var o = {
      url: '../print/printFactory.php?e_id=' + myId,
      type: 'post',
      dataType: 'json',
      beforeSubmit: validate,
      success: showResult,
      timeout: options.timeout ? options.timeout : 10000,
      error: function (xhr, textStatus) {
        showHideWorking("hide");
        var msg;
        if (textStatus === 'timeout') {
          msg = '<?php echo _mb("Zeitüberschreitung: Der Druckvorgang hat zu lange gedauert und wurde abgebrochen."); ?>';
        } else {
          msg = '<?php echo _mb("Serverfehler: Der Druckvorgang konnte nicht abgeschlossen werden. Bitte versuchen Sie es erneut."); ?>';
        }
        if (pfiErrorCallback) {
          var cb = pfiErrorCallback;
          pfiErrorCallback = null;
          cb(msg);
        } else {
          alert(msg);
        }
      }
    };
    $("#" + myId + "_form").ajaxForm(o);
  };

  /**
   * Change status of the working elements. These should begin with "$myId_working"
   *
   * @param {String} newStatus either "hide or "show"
   */
  var showHideWorking = function (newStatus) {
    if (newStatus == "hide") {
      $("[id^='" + myId + "_working']").hide();
    } else {
      $("[id^='" + myId + "_working']").show();
    }
  };

  /**
   * update form values helper function
   *
   */
  var updateFormField = function (formData, key, value) {
    for (var j = 0; j < formData.length; j++) {
      if (formData[j].name == key) {
        formData[j].value = value;
        break;
      }
    }
  };

  var getCurrentResolution = function (type) {

    // default resolution is 72 dpi
    var dpi = 72;

    // set resolution according to map configuration in template
    for (var i in actualConfig.pages) {
      var page = actualConfig.pages[i];
      for (var j in page.elements) {
        var el = page.elements[j];
        if (type === el.type && typeof el.res_dpi === "number") {
          dpi = el.res_dpi;
        }
      }
    }
    // set resolution according to resolution select box (if present)

    // check if hq print is requested
    var resolutionControl = null;
    for (var i in actualConfig.controls) {
      var c = actualConfig.controls[i];
      try {
        for (var j in c.pageElementsLink) {
          if (c.pageElementsLink[j] === "res_dpi") {
            resolutionControl = typeof c.id === "string" &&
            c.id.length > 0 ? $("#" + c.id) : null;
          }
        }
      } catch (e) {
      }
    }
    if (resolutionControl !== null && resolutionControl.size() === 1) {
      dpi = resolutionControl.val();
    }
    return parseInt(dpi, 10);
  };

  var replaceMapFileForHighQualityPrint = function (currentMapUrl, type) {
    var dpi = getCurrentResolution(type);
    // replace map file with hq map file (if configured)
    var hqmapfiles = $.isArray(options.highqualitymapfiles) ?
      options.highqualitymapfiles : [];
    for (var i = 0; i < hqmapfiles.length; i++) {
      var exp = new RegExp(hqmapfiles[i].pattern);
      if (hqmapfiles[i].pattern && typeof currentMapUrl === "string" && currentMapUrl.match(exp)) {
        // check if mapping in current resolution exists
        var resolutions = hqmapfiles[i].replacement;
        var resolutionExists = false;
        for (var r in resolutions) {
          if (parseInt(r, 10) === dpi) {
            resolutionExists = true;
          }
        }
        if (resolutionExists) {
          // replace with hqmapfile
          var hqmapfile = resolutions[dpi];
          currentMapUrl = currentMapUrl.replace(exp, hqmapfile);
        }
      }
    }
    return currentMapUrl;
  };

  /**
   * Validates and updates form data values.
   * Adds the elements before the submit button.
   *
   * @see jquery.forms#beforeSubmitHandler
   */
  var validate = function (formData, jqForm, params) {
    pfiCancelled = false;
    showHideWorking("show");

    // map urls
    var ind = getMapObjIndexByName(myTarget);
    var mapObj = mb_mapObj[ind];
    var f = jqForm[0];
    f.map_url.value = '';
    f.opacity.value = "";

    var scale = f.scale.value || mapObj.getScale();
    scale = parseInt(scale, 10);

    var legendUrlArray = [];
    var legendUrlArrayReverse = [];
    f.overview_url.value = '';

    // Force-include cadastral layers in the printed map image even when unchecked in tree.
    // We temporarily set gui_layer_visible=1 so getLayers()/getMapUrl() picks them up.
    var pfiCadMapPatch = [];
    if (printFeatureInfoData !== null &&
        printFeatureInfoData.pfiCadastralWmsId &&
        printFeatureInfoData.pfiCadastralLayerNames &&
        printFeatureInfoData.pfiCadastralLayerNames.length > 0) {
      for (var cadWmsI = 0; cadWmsI < mapObj.wms.length; cadWmsI++) {
        if (mapObj.wms[cadWmsI].wms_id === printFeatureInfoData.pfiCadastralWmsId) {
          for (var cadLyrI = 0; cadLyrI < mapObj.wms[cadWmsI].objLayer.length; cadLyrI++) {
            var cadLyrObj = mapObj.wms[cadWmsI].objLayer[cadLyrI];
            if (printFeatureInfoData.pfiCadastralLayerNames.indexOf(cadLyrObj.layer_name) >= 0) {
              pfiCadMapPatch.push({ layer: cadLyrObj, orig: cadLyrObj.gui_layer_visible });
              cadLyrObj.gui_layer_visible = 1;
            }
          }
          break;
        }
      }
    }

    // When printing featureInfo, only show legend for layers checked in the dialog.
    // printFeatureInfoData.urls already holds only the checked entries (unchecked
    // items are spliced out by the checkbox handler).  Build a lookup set from the
    // LAYERS= parameter of each request URL so we can filter both legend loops below.
    var pfiCheckedLayerNames = null;
    if (printFeatureInfoData !== null &&
        printFeatureInfoData.urls &&
        printFeatureInfoData.urls.length > 0) {
      pfiCheckedLayerNames = {};
      for (var pci = 0; pci < printFeatureInfoData.urls.length; pci++) {
        var pfiReq = printFeatureInfoData.urls[pci].request || '';
        var pfiLyrMatch = pfiReq.match(/[?&]LAYERS=([^&]*)/i);
        if (pfiLyrMatch) {
          var pfiLyrList = decodeURIComponent(pfiLyrMatch[1]).split(',');
          for (var pli = 0; pli < pfiLyrList.length; pli++) {
            if (pfiLyrList[pli]) {
              pfiCheckedLayerNames[pfiLyrList[pli]] = true;
            }
          }
        }
      }
    }

    if (options.reverseLegend == 'true') {
      for (var i = mapObj.wms.length - 1; i >= 0; i--) {
        var currentWms = mapObj.wms[i];
        if (currentWms.gui_wms_visible > 0) {
          if (currentWms.mapURL != false && currentWms.mapURL != 'false' && currentWms.mapURL != '') {
            var wmsLegendObj = [];

            var layers = currentWms.getLayers(mapObj, scale);
            for (var j = 0; j < layers.length; j++) {
              var currentLayer = currentWms.getLayerByLayerName(layers[j]);
              // TODO: add only visible layers
              var isVisible = (currentLayer.gui_layer_visible === 1);
              var hasNoChildren = (!currentLayer.has_childs);
              if (isVisible && hasNoChildren) {
                // In print featureInfo mode, skip layers not checked in the dialog
                if (pfiCheckedLayerNames !== null && !pfiCheckedLayerNames[currentLayer.layer_name]) {
                  continue;
                }
                var layerLegendObj = {};
                layerLegendObj.name = currentLayer.layer_name;
                layerLegendObj.title = currentWms.getTitleByLayerName(currentLayer.layer_name);
                var layerStyle = currentWms.getCurrentStyleByLayerName(currentLayer.layer_name);
                if (layerStyle === false || layerStyle === "") {
                  layerStyle = "";
                }
                layerLegendObj.legendUrl = currentWms.getLegendUrlByGuiLayerStyle(currentLayer.layer_name, layerStyle);
                // Skip invalid/parent legend URLs that contain multiple '?' (concatenated URLs)
                if (layerLegendObj.legendUrl !== false && (layerLegendObj.legendUrl.split('?').length - 1) <= 1) {
                    //if wms id is not excluded from printing
                    if (!array_contains(exclude,currentWms.wms_id)){
                    	//alert("The legend of the WMS with id " + JSON.stringify(currentWms.wms_id) + " should be printed");
        				wmsLegendObj.push(layerLegendObj);
    			    } else {
    			    	//alert("The legend of the WMS with id " + JSON.stringify(currentWms.wms_id) + " should not be printed");
    			    }
                }
              }
            }
            if (wmsLegendObj.length > 0) {
              var tmpObj = {};
              tmpObj[currentWms.wms_currentTitle] = wmsLegendObj;
              legendUrlArrayReverse.push(tmpObj);
            }
          }
        }
      }
    }

    for (var i = 0; i < mapObj.wms.length; i++) {
      var currentWms = mapObj.wms[i];
      if (currentWms.gui_wms_visible > 0) {
        if (currentWms.mapURL != false && currentWms.mapURL != 'false' && currentWms.mapURL != '') {
          if (f.map_url.value != "") {
            f.map_url.value += '___';
          }
          if (f.opacity.value != "") {
            f.opacity.value += '___';
          }
          var currentMapUrl = mapObj.getMapUrl(i, mapObj.getExtentInfos(), scale);

          currentMapUrl = replaceMapFileForHighQualityPrint(currentMapUrl, "map");
          f.map_url.value += currentMapUrl;
          f.opacity.value += currentWms.gui_wms_mapopacity;

          var wmsLegendObj = [];

          var layers = currentWms.getLayers(mapObj, scale);
          for (var j = 0; j < layers.length; j++) {
            var currentLayer = currentWms.getLayerByLayerName(layers[j]);
            // TODO: add only visible layers
            var isVisible = (currentLayer.gui_layer_visible === 1);
            var hasNoChildren = (!currentLayer.has_childs);
            if (isVisible && hasNoChildren) {
              // In print featureInfo mode, skip layers not checked in the dialog
              if (pfiCheckedLayerNames !== null && !pfiCheckedLayerNames[currentLayer.layer_name]) {
                continue;
              }
              var layerLegendObj = {};
              layerLegendObj.name = currentLayer.layer_name;
              layerLegendObj.title = currentWms.getTitleByLayerName(currentLayer.layer_name);
              var layerStyle = currentWms.getCurrentStyleByLayerName(currentLayer.layer_name);
              if (layerStyle === false || layerStyle === "") {
                layerStyle = "";
              }
              layerLegendObj.legendUrl = currentWms.getLegendUrlByGuiLayerStyle(currentLayer.layer_name, layerStyle);
              // Skip invalid/parent legend URLs that contain multiple '?' (concatenated URLs)
              if (layerLegendObj.legendUrl !== false && (layerLegendObj.legendUrl.split('?').length - 1) <= 1) {
                //if wms id is not excluded from printing
                if (!array_contains(exclude,currentWms.wms_id)){
                	//alert("The legend of the WMS with id " + JSON.stringify(currentWms.wms_id) + " should be printed");
    				wmsLegendObj.push(layerLegendObj);
			    } else {
			    	//alert("The legend of the WMS with id " + JSON.stringify(currentWms.wms_id) + " should not be printed");
			    }
              }
            }
          }
          if (wmsLegendObj.length > 0) {
            var tmpObj = {};
            tmpObj[currentWms.wms_currentTitle] = wmsLegendObj;
            if (options.reverseLegend == 'true') {
              legendUrlArray = legendUrlArrayReverse;
            } else {
              legendUrlArray.push(tmpObj);
            }
          }
        }
      }
    }

    // Restore cadastral layer visibility after map URLs have been collected
    for (var cadRI = 0; cadRI < pfiCadMapPatch.length; cadRI++) {
      pfiCadMapPatch[cadRI].layer.gui_layer_visible = pfiCadMapPatch[cadRI].orig;
    }

    var legendUrlArrayJson = $.toJSON(legendUrlArray);
    updateFormField(formData, "legend_url", legendUrlArrayJson);
    updateFormField(formData, "map_url", f.map_url.value);
    updateFormField(formData, "scale", scale);
    updateFormField(formData, "opacity", f.opacity.value);

    // overview_url
    var ind_overview = getMapObjIndexByName('overview');
    if (ind_overview !== undefined && mb_mapObj[ind_overview].mapURL != false) {
      var overviewUrl = mb_mapObj[ind_overview].mapURL;
      overviewUrl = $.isArray(overviewUrl) ? overviewUrl[0] : overviewUrl;

      f.overview_url.value = replaceMapFileForHighQualityPrint(overviewUrl, "overview");

      updateFormField(formData, "overview_url", f.overview_url.value);
    }

    updateFormField(formData, "map_scale", mb_getScale(myTarget));
    // write the measured coordinates
    if (typeof (mod_measure_RX) !== "undefined") {
      var tmp_x = '';
      var tmp_y = '';
      for (i = 0; i < mod_measure_RX.length; i++) {
        if (tmp_x != '') {
          tmp_x += ',';
        }
        tmp_x += mod_measure_RX[i];
      }
      for (i = 0; i < mod_measure_RY.length; i++) {
        if (tmp_y != '') {
          tmp_y += ',';
        }
        tmp_y += mod_measure_RY[i];
      }
      updateFormField(formData, "measured_x_values", tmp_x);
      updateFormField(formData, "measured_y_values", tmp_y);
    }

    //write the permanent highlight image, if defined

    var markers = [];
    var pixelpos = null;
    var realpos = [null, null];
    var feature = null;

    if (typeof GlobalPrintableGeometries != "undefined") {

      for (var idx = 0; idx < GlobalPrintableGeometries.count(); idx++) {
        feature = GlobalPrintableGeometries.get(idx);
        realpos = feature.get(0).get(0);
        var path = feature.e.getElementValueByName("Mapbender:icon");
        // The offsets are set to 40, meaning that images will need to be 80 x 80 with the tip of the marker- pixel being in the middle
        markers.push({
          position: [realpos.x, realpos.y],
          path: path,
          width: 40 * 2,
          height: 40 * 2,
          offset_x: 40,
          offset_y: 40
        });
      }
      var permanentImage = JSON.stringify(markers);
      updateFormField(formData, "mypermanentImage", permanentImage);

    }
    var $jqForm = $(jqForm);
    if ($jqForm.find('[name="svg_extent"]').length) {
      var ext = $("#mapframe1:maps").mapbender().extent;
      updateFormField(formData, "svg_extent", ext.min.x + ',' + ext.min.y + ',' + ext.max.x + ',' + ext.max.y);
      if ($jqForm.find('[name="map_svg_kml"]').length) {
        var kml = $('#mapframe1').data('kml');
        updateFormField(formData, "map_svg_kml", "");
        if (kml._kmls && $('#kml-rendering-pane svg:first').length) {
          for (var key in kml._kmls) { // object exists -> add svg
            var svgStr = $($('#kml-rendering-pane').get(0)).html();
            /* TODO start bug fix: multiple attributes xmlns="http://www.w3.org/2000/svg" by root svg at IE 9,10,11*/
            var root = svgStr.match(/^<svg[^>]+/g);
            if (root[0].match(/xmlns=["']http:\/\/www.w3.org\/2000\/svg["']/g).length > 1) {
              var svg1 = root[0].replace(/ xmlns=["']http:\/\/www.w3.org\/2000\/svg["']/g, '');
              updateFormField(formData, "map_svg_kml", svg1 + ' xmlns="http://www.w3.org/2000/svg"' + svgStr.substring(root[0].length));
            } else {
              updateFormField(formData, "map_svg_kml", svgStr);
            }
            /* end bug fix */
            break;
          }
        }
      }
      if ($jqForm.find('[name="map_svg_measures"]').length > 0) {
        if ($('#measure_canvas svg:first').length) {
          var svgStr = $('#measure_canvas').html();
          /* TODO start bug fix: multiple attributes xmlns="http://www.w3.org/2000/svg" by root svg at IE 9,10,11*/
          var root = svgStr.match(/^<svg[^>]+/g);
          if (root[0].match(/xmlns=["']http:\/\/www.w3.org\/2000\/svg["']/g).length > 1) {
            var svg1 = root[0].replace(/ xmlns=["']http:\/\/www.w3.org\/2000\/svg["']/g, '');
            updateFormField(formData, "map_svg_measures", svg1 + ' xmlns="http://www.w3.org/2000/svg"' + svgStr.substring(root[0].length));
          } else {
            updateFormField(formData, "map_svg_measures", svgStr);
          }
        } else {
          updateFormField(formData, "map_svg_measures", '');
        }
      }
    }

    // feature info data
    if (printFeatureInfoData !== null) {
      updateFormField(formData, "printPDF_template", printFeatureInfoData.config);
      formData.push({
        name: 'featureInfo',
        value: JSON.stringify(printFeatureInfoData)
      });
    }

    if (f.map_url.value != "" && typeof f.map_url.value != 'undefined' && f.map_url.value != false && f.map_url.value != 'false') {
      //all fields are ok wait for pdf
    } else {
      showHideWorking("hide");
      alert('<?php echo _mb('No active maplayers in current print extent, please choose another extent/position for your template frame!'); ?>');
      return false;
    }
  };

  /**
   * Method: showResult
   *
   * load the generated PDF from the returned URL as an attachment,
   * that triggers a download popup or is displayed in PDF plugin.
   */
  var showResult = function (res, text) {
    if (pfiCancelled) {
      showHideWorking("hide");
      return;
    }
    if (text == 'success') {
      var $downloadFrame = $("#" + myId + "_frame");
      if ($downloadFrame.size() === 0) {
        $downloadFrame = $(
          "<iframe id='" + myId + "_frame' name='" +
          myId + "_frame' width='0' height='0' style='display:none'></iframe>"
        ).appendTo("body");
      }
      var pdfUrl = stripslashes(res.outputFileName);
      if (printFeatureInfoData !== null) {
        // FeatureInfo print: show a clickable download link in the progress area
        var $progressWrap = $("[id='pfi-progress-wrap']");
        var $progressLabel = $("[id='pfi-progress-label']");
        $progressLabel.html(
          '<span><?php echo _mb("PDF fertig:"); ?></span> <a href="' + pdfUrl + '" target="_blank" ' +
          'style="font-weight:bold;color:#1a5fa8;text-decoration:none;">' +
          '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 16 16" fill="currentColor" style="margin-bottom:-3px;margin-right:3px;">' +
            '<path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>' +
            '<path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>' +
          '</svg>' +
          '<?php echo _mb("Herunterladen"); ?></a>'
        );
        $progressWrap.show();
        showHideWorking("hide");
        $("#" + myId).trigger("load");
      } else {
        // Normal print (Werkzeug/Drucken): restore original delivery behaviour
        if ($.browser.msie) {
          $('<div></div>')
            .attr('id', 'ie-print')
            .append($('<p>Ihr PDF wurde erstellt und kann nun heruntergeladen werden:</p>'))
            .append($('<a>Zum Herunterladen hier klicken</a>')
              .attr('href', pdfUrl)
              .click(function () {
                $(this).parent().dialog('destroy');
              }))
            .appendTo('body')
            .dialog({
              title: 'PDF-Druck'
            });
        } else {
          window.frames[myId + "_frame"].location.href = pdfUrl;
        }
        showHideWorking("hide");
        $("#" + myId).trigger("load");
      }
    } else {
      /* something went wrong */
      $("#" + myId + "_result").html(text);
    }
  };

  /**
   * Generates form elements as specified in the config controls object.
   * Adds the elements before the submit button.
   *
   * @param {Object} json the config object in json
   */
  var buildForm = function () {
    $(".print_option_dyn").remove();
    $("#printboxScale").remove();
    $("#printboxCoordinates").remove();
    $("#printboxAngle").remove();
    var str = "";
    str += '<input type="hidden" name="printboxScale" id="printboxScale">\n';
    str += '<input type="hidden" name="printboxCoordinates" id="printboxCoordinates">\n';
    str += '<input type="hidden" name="printboxAngle" id="printboxAngle">\n';
    for (var item in actualConfig.controls) {
      var element = actualConfig.controls[item];
      var element_id = myId + "_" + element.id;
      if (element.type != "hidden") {
        str += '<div class="print_option_dyn">\n';
        str += '<label class="print_label" for="' + element.id + '">' + element.label + '</label>\n';
      } else {
        str += '<div class="print_option_dyn" style="display:none;">\n';
      }

      if (element.maxCharacter) {
        var maxLength = 'maxlength="' + element.maxCharacter + '"';

      } else {
        var maxLength = "";
      }
      switch (element.type) {
        case "text":
          str += '<input type="' + element.type + '" name="' + element.id + '" id="' + element.id + '" size="' + element.size + '" ' + maxLength + '><br>\n';
          break;
        case "hidden":
          str += '<input type="' + element.type + '" name="' + element.id + '" id="' + element.id + '">\n';
          break;
        case "textarea":
          str += '<textarea id="' + element.id + '" name="' + element.id + '" size="' + element.size + '" ' + maxLength + '></textarea><br>\n';
          break;
        case "select":
          str += '<select id="' + element.id + '" name="' + element.id + '" size="1">\n';
          for (var option_index in element.options) {
            option = element.options[option_index];
            var selected = option.selected ? option.selected : "";
            str += '<option ' + selected + ' value="' + option.value + '">' + option.label + '</option>\n';
          }
          str += '</select><br>\n';
          break;
      }
      str += '</div>\n';
    }
    if (str) {
      $('textarea[maxlength]').live('keyup change', function () {
        var str = $(this).val()
        var mx = parseInt($(this).attr('maxlength'))
        if (str.length > mx) {
          $(this).val(str.substr(0, mx))
          return false;
        }
      });
      $("#" + myId + "_formsubmit").before(str);
      if ($("#scale").is("input")) {
        $("#scale").keydown(function (e) {
          if (e.keyCode === 13) {
            return false;
          }
        }).keyup(function (e) {
          if (e.keyCode === 13) {
            return false;
          }

          var scale = parseInt(this.value, 10);
          if (isNaN(scale) || typeof printBox === "undefined") {
            return true;
          }

          if (scale < 10) {
            return true;
          }
          printBox.setScale(scale);
          return true;
        });
      } else {
        $("#scale").change(function (e) {
          var scale = parseInt(this.value, 10);
          if (isNaN(scale) || typeof printBox === "undefined") {
            return true;
          }

          if (scale < 10) {
            return true;
          }
          printBox.setScale(scale);
          return true;
        });
      }

      $("#angle").keydown(function (e) {
        if (e.keyCode === 13) {
          return false;
        }
      }).keyup(function (e) {
        if (e.keyCode === 13) {
          return false;
        }
        var angle = parseInt(this.value, 10);
        if (isNaN(angle) || typeof printBox === "undefined") {
          return true;
        }
        printBox.setAngle(angle);
        return true;
      });
    }
  };

  /**
   * Generates the configuration select element from the gui element vars
   * mbPrintConfigFilenames and mbPrintConfigTitles
   */
  var buildConfigSelector = function () {
    var str = "";
    str += '<label class="print_label" for="printPDF_template">Vorlage</label>\n';
    str += '<select id="printPDF_template" name="printPDF_template" size="1">\n';
    for (var i = 0; i < mbPrintConfigFilenames.length; i++) {
      str += '<option value="' + mbPrintConfigFilenames[i] + '">' + mbPrintConfigTitles[i] + '</option>\n';
    }
    str += '</select><img id="printPDF_handle" src="../print/img/shape_handles.png" title="<?php echo htmlentities(_mb("use print box"), ENT_QUOTES, "UTF-8");?>">\n';
    if (str) {
      $("#printPDF_selector").append(str).find("#printPDF_template").change(function () {
        printObj.loadConfig(mbPrintConfigFilenames[this.selectedIndex], function () {
          printObj.createPrintBox()
        });
      });

      $("#printPDF_handle").click(function () {
        if (printBox) {
          if (printBox.isVisible()) {
            showHidePrintBox("hide");
            $("#printboxScale").val($("#printPDF_form #scale").val());
            $("#printboxCoordinates").val($("#printPDF_form #coordinates").val());
            $("#printboxAngle").val($("#printPDF_form #angle").val());

            $("#printPDF_form #scale").val("");
            $("#printPDF_form #coordinates").val("");
            $("#printPDF_form #angle").val("");
          } else {
            showHidePrintBox("show");
            $("#printPDF_form #scale").val($("#printboxScale").val());
            $("#printPDF_form #coordinates").val($("#printboxCoordinates").val());
            $("#printPDF_form #angle").val($("#printboxAngle").val());
          }
        } else {
          printObj.createPrintBox();
        }

      });
      $("#printPDF_working").bgiframe({
        src: "BLOCKED SCRIPT'&lt;html&gt;&lt;/html&gt;';",
        width: 200,
        height: 200
      });
    }
  };

  var stripslashes = function (str) {
    return (str + '').replace(/\0/g, '0').replace(/\\([\\'"])/g, '$1');
  };

  var printFeatureInfoData = null;
  var pfiSubmitting = false;
  var pfiPixelCenter = null;
  var pfiPollInterval = null;
  var pfiCancelled = false;

  function fixMapFormValues (printInfo) {
    var map = getMapObjByName(myTarget);
    var rawScale = map.getScale();
    var magnitude = Math.pow(10, Math.floor(Math.log(rawScale) / Math.LN10));
    var scale = Math.round(rawScale / magnitude) * magnitude;
    $("#scale").val(scale.toString());

    var realWidthInM = scale * getPDFMapSize("width") / 1000;
    var realHeightInM = scale * getPDFMapSize("height") / 1000;

    var bbox = [
      printInfo.point.x - 0.5 * realWidthInM,
      printInfo.point.y - 0.5 * realHeightInM,
      printInfo.point.x + 0.5 * realWidthInM,
      printInfo.point.y + 0.5 * realHeightInM
    ];

    $("#coordinates").val(bbox.join(","))
  }

  this.printFeatureInfo = function (printInfo, $featureInfoDialog) {
    var printObj = this;
    var oldConfig = actualConfig;
    var $dialog;
    var stopSpotlight = null;
    var pfiRestoring = false;

    function startSpotlightOverlay() {
      var map = getMapObjByName(myTarget);
      var $mapEl = $(map.getDomElement());
      var mapW = $mapEl.width();
      var mapH = $mapEl.height();
      var ns = 'http://www.w3.org/2000/svg';
      var svgEl = document.createElementNS(ns, 'svg');
      svgEl.id = 'pfi-spotlight-overlay';
      svgEl.setAttribute('style',
        'position:absolute;top:0;left:0;width:' + mapW + 'px;height:' + mapH +
        'px;z-index:999;pointer-events:none;');
      var pathEl = document.createElementNS(ns, 'path');
      pathEl.setAttribute('fill', 'rgba(0,0,0,0.45)');
      pathEl.setAttribute('fill-rule', 'evenodd');
      svgEl.appendChild(pathEl);

      // Red circle marker at the center of the print box
      var circleEl = document.createElementNS(ns, 'circle');
      circleEl.setAttribute('r', '4');
      circleEl.setAttribute('fill', '#ff0000');
      circleEl.setAttribute('stroke', '#ff0000');
      circleEl.setAttribute('stroke-width', '2');
      circleEl.setAttribute('fill-opacity', '0.5');
      circleEl.setAttribute('style', 'stroke-width: 2px; fill-opacity: 0.5;');
      svgEl.appendChild(circleEl);

      $mapEl[0].appendChild(svgEl);

      function updateOverlay() {
        var coords = $('#printPDF_form #coordinates').val();
        if (!coords) return;
        var parts = coords.split(',');
        var minx = parseFloat(parts[0]), miny = parseFloat(parts[1]);
        var maxx = parseFloat(parts[2]), maxy = parseFloat(parts[3]);

        // Convert unrotated map corners to pixel positions
        var c0 = makeRealWorld2mapPos(myTarget, minx, miny);   // bottom-left
        var c1 = makeRealWorld2mapPos(myTarget, maxx, miny);   // bottom-right
        var c2 = makeRealWorld2mapPos(myTarget, maxx, maxy);   // top-right
        var c3 = makeRealWorld2mapPos(myTarget, minx, maxy);   // top-left

        // Apply rotation (same formula as printbox.js rotate())
        var angle = parseFloat($('#printPDF_form #angle').val() || '0');
        if (angle !== 0) {
          var ctr = makeRealWorld2mapPos(myTarget, (minx + maxx) / 2, (miny + maxy) / 2);
          var cx = ctr[0], cy = ctr[1];
          var rad = angle * Math.PI / 180;
          var cos = Math.cos(rad), sin = Math.sin(rad);
          function rotPt(p) {
            var dx = p[0] - cx, dy = p[1] - cy;
            return [cx + dx * cos + dy * sin, cy - dx * sin + dy * cos];
          }
          c0 = rotPt(c0); c1 = rotPt(c1); c2 = rotPt(c2); c3 = rotPt(c3);
        }

        var d = 'M0 0 L' + mapW + ' 0 L' + mapW + ' ' + mapH + ' L0 ' + mapH + ' Z ' +
          'M' + c3[0] + ' ' + c3[1] +
          ' L' + c2[0] + ' ' + c2[1] +
          ' L' + c1[0] + ' ' + c1[1] +
          ' L' + c0[0] + ' ' + c0[1] + ' Z';
        pathEl.setAttribute('d', d);

        // Pin the red circle to the visual center of the print box rectangle
        var boxCx = (c0[0] + c1[0] + c2[0] + c3[0]) / 4;
        var boxCy = (c0[1] + c1[1] + c2[1] + c3[1]) / 4;
        circleEl.setAttribute('cx', boxCx);
        circleEl.setAttribute('cy', boxCy);
      }

      var intervalId = setInterval(updateOverlay, 80);
      updateOverlay();

      return function() {
        clearInterval(intervalId);
        $('#pfi-spotlight-overlay').remove();
      };
    }

    // Auto-inject Hintergrundkarte/Flurstücke as a permanent Abfragen entry.
    // Strategy: find the WMS titled "Hintergrundkarte", then find the "Flurstücke" sublayer within it.
    // This avoids false matches when multiple WMS/layers share the word "Flurstücke".
    var pfiHintergrundkarteKeyword = /hintergrundkarte/i;
    var pfiFlurstuckeKeyword = /flurst[uü]c?ke/i;
    var pfiMapObj = mb_mapObj[getMapObjIndexByName(myTarget)];
    var pfiUrls = printInfo.urls || [];

    // Skip injection if Hintergrundkarte is already represented in the urls list
    var pfiCadastralAlreadyListed = false;
    for (var pci = 0; pci < pfiUrls.length; pci++) {
      if (pfiUrls[pci] && pfiUrls[pci].title && pfiHintergrundkarteKeyword.test(pfiUrls[pci].title)) {
        pfiCadastralAlreadyListed = true;
        break;
      }
    }
    if (!pfiCadastralAlreadyListed) {
      // Step 1: find the Hintergrundkarte WMS
      var pfiFountCadWms = null;
      for (var pci = 0; pci < pfiMapObj.wms.length; pci++) {
        var wmsToTest = pfiMapObj.wms[pci];
        var wmsTitleToCheck = (wmsToTest.wms_currentTitle || '') + '|' + (wmsToTest.wms_title || '');
        if (pfiHintergrundkarteKeyword.test(wmsTitleToCheck)) {
          pfiFountCadWms = wmsToTest;
          break;
        }
      }
      if (pfiFountCadWms !== null) {
        // Step 2: find the Flurstücke sublayer within Hintergrundkarte
        var pfiFlurstuckeLayer = null;
        if (pfiFountCadWms.objLayer) {
          for (var plj = 0; plj < pfiFountCadWms.objLayer.length; plj++) {
            var pfiLayerTitle = pfiFountCadWms.objLayer[plj].gui_layer_title || pfiFountCadWms.objLayer[plj].layer_name || '';
            if (pfiFlurstuckeKeyword.test(pfiLayerTitle)) {
              pfiFlurstuckeLayer = pfiFountCadWms.objLayer[plj];
              break;
            }
          }
        }
        if (pfiFlurstuckeLayer !== null) {
          // If the layer is already visible and queryable in the tree it will already be in pfiUrls — skip injection to avoid duplicates
          var pfiLayerAlreadyActive = (pfiFlurstuckeLayer.gui_layer_visible == 1 && pfiFlurstuckeLayer.gui_layer_querylayer == 1);
          if (pfiLayerAlreadyActive) {
            // Still store the cadastral WMS/layer references so validate() can force them into the GetMap URL
            printInfo.pfiCadastralWmsId = pfiFountCadWms.wms_id;
            printInfo.pfiCadastralLayerNames = [pfiFlurstuckeLayer.layer_name];
          }
        }
        if (pfiFlurstuckeLayer !== null && !pfiLayerAlreadyActive) {
          var pfiPxCenter = makeRealWorld2mapPos(myTarget, printInfo.point.x, printInfo.point.y);

          // Temporarily force-enable only the Flurstücke layer so getFeatureInfoRequest() includes it
          var pfiFlurstOrigVisible    = pfiFlurstuckeLayer.gui_layer_visible;
          var pfiFlurstOrigQuerylayer = pfiFlurstuckeLayer.gui_layer_querylayer;
          pfiFlurstuckeLayer.gui_layer_visible    = 1;
          pfiFlurstuckeLayer.gui_layer_querylayer = 1;

          var pfiCadReq = pfiFountCadWms.getFeatureInfoRequest(pfiMapObj, { x: pfiPxCenter[0], y: pfiPxCenter[1] });

          // Restore original layer state immediately
          pfiFlurstuckeLayer.gui_layer_visible    = pfiFlurstOrigVisible;
          pfiFlurstuckeLayer.gui_layer_querylayer = pfiFlurstOrigQuerylayer;

          if (pfiCadReq) {
            var pfiCadTitle = pfiFlurstuckeLayer.gui_layer_title || pfiFlurstuckeLayer.layer_name;
            var pfiFlurstStyle = pfiFountCadWms.getCurrentStyleByLayerName(pfiFlurstuckeLayer.layer_name);
            if (pfiFlurstStyle === false || pfiFlurstStyle === '') { pfiFlurstStyle = 'default'; }
            var pfiFlurstLegendUrl = pfiFountCadWms.getLegendUrlByGuiLayerStyle(pfiFlurstuckeLayer.layer_name, pfiFlurstStyle);
            pfiUrls.push({
              title: pfiCadTitle,
              request: pfiCadReq,
              legendurl: pfiFlurstLegendUrl || '',
              inBbox: true
            });
            printInfo.urls = pfiUrls;
            // Store WMS id + Flurstücke layer name so validate() can force it into the GetMap URL
            printInfo.pfiCadastralWmsId = pfiFountCadWms.wms_id;
            printInfo.pfiCadastralLayerNames = [pfiFlurstuckeLayer.layer_name];
          }
        }
      }
    }

    if (!printInfo.originalUrls) {
      printInfo.originalUrls = printInfo.urls.slice();
    } else {
      printInfo.urls = printInfo.originalUrls.slice();
    }

    var $dialogDiv = $("<div class='pfi-maindiv'>");
    var $backgroundDiv = $("<div class='pfi-selectbackground'>");
    var $abfragenDiv = $("<div class='pfi-abfragen'>");
    $dialogDiv.append($backgroundDiv).append($abfragenDiv);

    // select for background

    var $backgroundSelect = $('<select size="4" multiple>');

    var ind = getMapObjIndexByName(myTarget);
    var mapObj = mb_mapObj[ind];

    var visCount = 0;

    mapObj.wms.forEach(function (wms) {
      if (wms.gui_wms_visible > 0 && wms.mapURL && wms.mapURL !== 'false') {
        $backgroundSelect.append(new Option(wms.wms_title, visCount.toString(), false, visCount === 0));
        visCount++;
      }
    });

    printInfo.backgroundWMS = $backgroundSelect.val().map(parseInt);

    $backgroundSelect.bind('change', function () {
      printInfo.backgroundWMS = $backgroundSelect.val().map(parseInt);
    });

    $backgroundDiv.append("<h3>Hintergrundkarte für abgefragte Ebene</h3>").append($backgroundSelect);

    // feature info ebenen

    var $abfragenH3 = $('<h3 style="margin-bottom:6px;">Abzufragende Ebene: </h3>');
    var $pfiInfoBtn = $('<button type="button" title="Format-Vorschau anzeigen" ' +
      'style="width:18px;height:18px;padding:0;line-height:1;font-size:12px;font-weight:bold;font-style:italic;' +
      'border-radius:50%;border:1px solid #888;background:#fff;color:#555;cursor:pointer;' +
      'vertical-align:middle;margin-left:4px;margin-bottom:2px;">i</button>');

    $pfiInfoBtn.bind('click', function () {
      var $infoDialog = $('<div style="overflow:auto;"></div>');
      $infoDialog.append(
        '<table style="border-collapse:collapse;width:100%;table-layout:fixed;"><tr>' +
          '<td style="text-align:center;padding:0 8px 0 0;vertical-align:top;width:50%;">' +
            '<div style="font-weight:bold;margin-bottom:6px;">HTML</div>' +
            '<img src="../img/pfi-format-html.png" alt="HTML-Vorschau" ' +
              'style="width:100%;height:auto;border:1px solid #ccc;">' +
          '</td>' +
          '<td style="text-align:center;padding:0 0 0 8px;vertical-align:top;width:50%;">' +
            '<div style="font-weight:bold;margin-bottom:6px;">Text</div>' +
            '<img src="../img/pfi-format-text.png" alt="Text-Vorschau" ' +
              'style="width:100%;height:auto;border:1px solid #ccc;">' +
          '</td>' +
        '</tr></table>'
      );
      $infoDialog.dialog({
        title: 'Format-Vorschau: HTML vs. Text',
        modal: true,
        resizable: true,
        width: 700,
        close: function () { $(this).dialog('destroy').remove(); }
      });
    });

    $abfragenH3.append($pfiInfoBtn);
    $abfragenDiv.append($abfragenH3);

    printInfo.urls.forEach(function (url, i) {
      var $checkBox = $('<input type="checkbox" checked>');

      $checkBox.bind('change', function () {
        if ($checkBox.is(':checked')) {
          printInfo.urls.splice(printInfo.originalUrls.indexOf(url), 0, url);
        } else {
          printInfo.urls.splice(printInfo.urls.indexOf(url), 1);
        }
      });

      $abfragenDiv.append($("<label class='pfi-abfragen-check'>" + url.title + "</label>").prepend($checkBox));

      var htmlRegex = /([?&]INFO_FORMAT=text\/)html/i;
      var textRegex = /([?&]INFO_FORMAT=text\/)plain/i;

      var $radioHTML = $('<input type="radio" name="pfi-print-format-' + i + '">');
      $abfragenDiv.append($("<label class='pfi-abfragen-radio'>HTML</label>").prepend($radioHTML));

      var $radioText = $('<input type="radio" name="pfi-print-format-' + i + '">');
      $abfragenDiv.append($("<label class='pfi-abfragen-radio'>Text</label>").prepend($radioText));

      if (htmlRegex.test(url.request)) {
        $radioHTML.attr('checked', 'checked');
      } else if (textRegex.test(url.request)) {
        $radioText.attr('checked', 'checked');
      }

      $radioHTML.bind('change', function () {
        url.request = url.request.replace(textRegex, '$1html');
      });

      $radioText.bind('change', function () {
        url.request = url.request.replace(htmlRegex, '$1plain');
      });
    });

    // Add input fields for print options (title, dpi, comment, scale)

    // Legend option checkbox
    var $optionsDiv = $('<div class="pfi-options" style="margin-top:8px;padding-top:6px;border-top:1px solid #ccc;">'
      + '<label style="cursor:pointer;">'
      + '<input type="checkbox" id="pfi_include_legend" checked style="margin-right:4px;">'
      + 'Legende einschlie&szlig;en'
      + '</label>'
      + '</div>');
    $dialogDiv.append($optionsDiv);

    function restore () {
      if (pfiRestoring) return;
      pfiRestoring = true;
      clearInterval(pfiPollInterval);
      pfiPollInterval = null;
      pfiCancelled = true;
      $("#" + myId).unbind("load.pfi");
      // Re-enable FeatureInfo clicks
      if (typeof Mapbender !== 'undefined' && Mapbender.enableFeatureInfo) {
        Mapbender.enableFeatureInfo();
      }
      if (stopSpotlight) { stopSpotlight(); stopSpotlight = null; }
      pfiPixelCenter = null;
      pfiSubmitting = false;
      pfiErrorCallback = null;
      // Reset progress bar for next use
      $dialogDiv.find('#pfi-progress-wrap').hide();
      $dialogDiv.find('#pfi-progress-bar').css('width', '0%');
      $dialogDiv.find('#pfi-progress-label').text('');
      $dialog.dialog('close').remove();
      $featureInfoDialog.dialog('open');
      actualConfig = oldConfig;
      printFeatureInfoData = null;
      buildForm();
      hookForm();
      destroyPrintBox();
    }

    printObj.loadConfig(mbPrintConfigPath + printInfo.config, function () {
      $featureInfoDialog.dialog('close');
      buildForm();
      fixMapFormValues(printInfo);
      printObj.createPrintBox(printInfo.point);
      pfiPixelCenter = makeRealWorld2mapPos(myTarget, printInfo.point.x, printInfo.point.y);
      stopSpotlight = startSpotlightOverlay();
      // Disable FeatureInfo clicks while the print dialog is open
      if (typeof Mapbender !== 'undefined' && Mapbender.disableFeatureInfo) {
        Mapbender.disableFeatureInfo();
      }
      printFeatureInfoData = printInfo;
      
      // Set initial scale value in dialog from the calculated scale
      var initialScale = $('#printPDF_form #scale').val();
      if (initialScale) {
        $('#pfi_scale').val(initialScale);
      }

      $dialog = $dialogDiv.dialog({
        autoOpen: true,
        modal: false,
        title: "<?php echo _mb("Print FeatureInfo"); ?>",
        width: 420,
        height: 'auto',
        maxHeight: 580,
        position: [20, 80],
        open: function () {
          $(this).closest('.ui-dialog').css({ top: '80px', left: '20px' });
        },
        close: function () {
          restore();
        },
        buttons: {
          "<?php echo _mb("Print"); ?>": function () {
            if (pfiSubmitting) return;
            pfiSubmitting = true;
            pfiCancelled = false;

            // Generate a unique progress token for this print job
            var pfiProgressToken = 'pfi' + Date.now().toString(36) + Math.random().toString(36).substr(2, 6);

            // Disable only the Print button to prevent double-click; keep Cancel active
            $(this).closest('.ui-dialog').find('.ui-dialog-buttonpane button').filter(function () {
              return $(this).text() === '<?php echo _mb("Print"); ?>';
            }).attr('disabled', 'disabled').css({ opacity: '0.5', cursor: 'default' });

            // Show real progress bar, hide old spinner
            $dialogDiv.find('#pfi-progress-wrap').show();
            $dialogDiv.find('#pfi-progress-bar').css('width', '0%');
            $dialogDiv.find('#pfi-progress-label').text('Druck wird gestartet...');

            // Copy dialog field values to the actual form fields in #printPDF_form
            $('#printPDF_form #title').val($dialogDiv.find('#pfi_title').val() || '');
            $('#printPDF_form #dpi').val($dialogDiv.find('#pfi_dpi').val() || '150');
            $('#printPDF_form #comment1').val($dialogDiv.find('#pfi_comment1').val() || '');
            var scaleVal = $dialogDiv.find('#pfi_scale').val();
            if (scaleVal) {
              $('#printPDF_form #scale').val(scaleVal);
            }

            // Inject the legend include flag as a hidden field
            $('#printPDF_form').find('[name="pfi_include_legend"]').remove();
            $('<input type="hidden" name="pfi_include_legend">').val($dialogDiv.find('#pfi_include_legend').is(':checked') ? '1' : '0').appendTo('#printPDF_form');

            // Inject the progress token as a hidden field into the print form
            $('#printPDF_form').find('[name="pfi_progress_token"]').remove();
            $('<input type="hidden" name="pfi_progress_token">').val(pfiProgressToken).appendTo('#printPDF_form');

            // Scale AJAX timeout dynamically based on active WMS service count.
            // map_url is not yet populated (it's filled in validate()), so count
            // directly from the map object — the same source validate() uses.
            (function () {
              var ind = getMapObjIndexByName(myTarget);
              var mapObj = ind !== undefined ? mb_mapObj[ind] : null;
              var activeWmsCount = 1;
              if (mapObj) {
                activeWmsCount = 0;
                for (var wi = 0; wi < mapObj.wms.length; wi++) {
                  var w = mapObj.wms[wi];
                  if (w.gui_wms_visible > 0 && w.mapURL && w.mapURL !== 'false') {
                    activeWmsCount++;
                  }
                }
                activeWmsCount = Math.max(1, activeWmsCount);
              }
              // Allow 10s per WMS service, minimum 90s
              options.timeout = Math.max(options.timeout || 90000, activeWmsCount * 10000);
            }());

            // Refresh GetFeatureInfo request URLs for any map panning that occurred
            // since the dialog was opened. pfiPixelCenter is always kept current by
            // eventAfterMapRequest, so rebuild each URL using the current map state.
            (function () {
              if (!pfiPixelCenter) return;
              var ind = getMapObjIndexByName(myTarget);
              var mapObj = ind !== undefined ? mb_mapObj[ind] : null;
              if (!mapObj) return;
              var clickPoint = { x: pfiPixelCenter[0], y: pfiPixelCenter[1] };

              // Build wms_id → wms object lookup
              var wmsById = {};
              for (var wi = 0; wi < mapObj.wms.length; wi++) {
                wmsById[mapObj.wms[wi].wms_id] = mapObj.wms[wi];
              }

              // Build wms_id lookup for Flurstücke by layer name
              var cadWmsId = printInfo.pfiCadastralWmsId || null;
              var cadLayerNames = printInfo.pfiCadastralLayerNames || [];

              for (var ui = 0; ui < printInfo.urls.length; ui++) {
                var urlEntry = printInfo.urls[ui];
                if (!urlEntry.inBbox) continue;

                var refreshed = false;

                // Case 1: Flurstücke — refresh using the stored cadastral WMS id.
                // Temporarily enable the layer so getFeatureInfoRequest() includes it.
                if (!refreshed && cadWmsId && cadLayerNames.length > 0) {
                  var cadWms = wmsById[cadWmsId];
                  if (cadWms) {
                    // Check if this url entry belongs to the cadastral WMS
                    var cadBase = cadWms.wms_getfeatureinfo ? cadWms.wms_getfeatureinfo.split('?')[0].toLowerCase() : '';
                    var entryBase = (urlEntry.request || '').split('?')[0].toLowerCase();
                    var isCad = cadBase && entryBase && entryBase.indexOf(cadBase) !== -1;
                    if (!isCad) {
                      // Also check by title match against cadastral layer names
                      for (var ci = 0; ci < cadLayerNames.length; ci++) {
                        if (urlEntry.title && urlEntry.title === cadLayerNames[ci]) { isCad = true; break; }
                      }
                    }
                    if (isCad) {
                      // Temporarily enable the Flurstücke layer
                      var cadPatches = [];
                      for (var li = 0; li < cadWms.objLayer.length; li++) {
                        var lyr = cadWms.objLayer[li];
                        if (cadLayerNames.indexOf(lyr.layer_name) >= 0) {
                          cadPatches.push({ layer: lyr, v: lyr.gui_layer_visible, q: lyr.gui_layer_querylayer });
                          lyr.gui_layer_visible = 1;
                          lyr.gui_layer_querylayer = 1;
                        }
                      }
                      var newReq = cadWms.getFeatureInfoRequest(mapObj, clickPoint);
                      for (var pi = 0; pi < cadPatches.length; pi++) {
                        cadPatches[pi].layer.gui_layer_visible = cadPatches[pi].v;
                        cadPatches[pi].layer.gui_layer_querylayer = cadPatches[pi].q;
                      }
                      if (newReq) {
                        var infoFmt = urlEntry.request.match(/[?&]INFO_FORMAT=([^&]+)/i);
                        if (infoFmt) { newReq = newReq.replace(/([?&]INFO_FORMAT=)[^&]+/i, '$1' + infoFmt[1]); }
                        urlEntry.request = newReq;
                        refreshed = true;
                      }
                    }
                  }
                }

                // Case 2: regular WMS layers — match by base URL
                if (!refreshed) {
                  for (var wi = 0; wi < mapObj.wms.length; wi++) {
                    var wms = mapObj.wms[wi];
                    if (!wms.wms_getfeatureinfo) continue;
                    var wmsBase = wms.wms_getfeatureinfo.split('?')[0].toLowerCase();
                    var urlBase = (urlEntry.request || '').split('?')[0].toLowerCase();
                    if (wmsBase && urlBase && urlBase.indexOf(wmsBase) !== -1) {
                      var newReq = wms.getFeatureInfoRequest(mapObj, clickPoint);
                      if (newReq) {
                        var infoFmt = urlEntry.request.match(/[?&]INFO_FORMAT=([^&]+)/i);
                        if (infoFmt) { newReq = newReq.replace(/([?&]INFO_FORMAT=)[^&]+/i, '$1' + infoFmt[1]); }
                        urlEntry.request = newReq;
                      }
                      break;
                    }
                  }
                }
              }

              // Keep originalUrls in sync so checkbox logic stays correct
              printInfo.originalUrls = printInfo.urls.slice();
              printFeatureInfoData = printInfo;
            }());

            hookForm();

            // Track last progress to prevent backwards jumps
            var pfiLastPercent = 0;

            // Poll the progress endpoint
            pfiPollInterval = setInterval(function () {
              $.getJSON('../print/printProgress.php', { token: pfiProgressToken }, function (data) {
                var pct = Math.min(100, parseInt(data.percent, 10) || 0);
                // Update label always (to show latest status)
                $dialogDiv.find('#pfi-progress-label').text(data.stepLabel || '');
                // Only update progress bar if moving forward
                if (pct >= pfiLastPercent) {
                  pfiLastPercent = pct;
                  $dialogDiv.find('#pfi-progress-bar').css('width', pct + '%');
                }
                if (data.error) {
                  clearInterval(pfiPollInterval);
                  pfiErrorCallback = null;
                  alert('<?php echo _mb("PDF-Erstellung fehlgeschlagen. Bitte versuchen Sie es erneut."); ?>');
                  restore();
                } else if (data.done) {
                  clearInterval(pfiPollInterval);
                }
              });
            }, 800);

            // Register error handler so hookForm's AJAX error (timeout, server error)
            // shows a proper message and closes the dialog
            pfiErrorCallback = function (msg) {
              clearInterval(pfiPollInterval);
              alert(msg || '<?php echo _mb("PDF-Erstellung fehlgeschlagen. Bitte versuchen Sie es erneut."); ?>');
              restore();
            };

            $("#" + myId).bind("load.pfi", function () {
              clearInterval(pfiPollInterval);
              pfiPollInterval = null;
              pfiErrorCallback = null;
              // Success: keep dialog open, show download link (set by showResult).
              // Reset submission state so user can print again; progress bar stays
              // visible with the download link until they close the dialog.
              pfiSubmitting = false;
              $dialogDiv.find('#pfi-progress-bar').css('width', '100%');
              $dialogDiv.closest('.ui-dialog').find('.ui-dialog-buttonpane button').filter(function () {
                return $(this).text() === '<?php echo _mb("Print"); ?>';
              }).removeAttr('disabled').css({ opacity: '', cursor: '' });
              showHideWorking("hide");
            });
            // $("." + myId + "_working").show();
            // $("." + myId + "_working_bg").show();
            $('#printPDF_form').submit();
          },
          "<?php echo _mb("Cancel"); ?>": restore
        }
      });

      // Proportional resize: ±buttons and direct scale input update the box
      function applyPfiScale(newScale) {
        if (isNaN(newScale) || newScale <= 0 || !printBox || !pfiPixelCenter) return;
        var nc = makeClickPos2RealWorldPos(myTarget, pfiPixelCenter[0], pfiPixelCenter[1]);
        printBox.setCenterMap({x: nc[0], y: nc[1]});
        printBox.setScale(newScale);
        $dialogDiv.find('#pfi_scale').val(newScale);
        $('#printPDF_form #scale').val(newScale);
      }

      $dialogDiv.find('#pfi_scale').bind('change', function () {
        applyPfiScale(parseInt($(this).val(), 10));
      });

      $dialogDiv.find('#pfi_scale_minus').bind('click', function () {
        var s = parseInt($dialogDiv.find('#pfi_scale').val(), 10);
        if (!isNaN(s) && s > 0) {
          applyPfiScale(Math.max(100, Math.round(s / 1.5 / 100) * 100));
        }
      });

      $dialogDiv.find('#pfi_scale_plus').bind('click', function () {
        var s = parseInt($dialogDiv.find('#pfi_scale').val(), 10);
        if (!isNaN(s) && s > 0) {
          applyPfiScale(Math.round(s * 1.5 / 100) * 100);
        }
      });

      // Append progress bar into the dialog content (hidden until print starts)
      $dialogDiv.append(
        '<div id="pfi-progress-wrap" style="display:none;margin:10px 4px 4px 4px;">' +
          '<div id="pfi-progress-label" style="font-size:12px;margin-bottom:4px;color:#333;">Wird gestartet...</div>' +
          '<div style="background:#ddd;border-radius:4px;height:18px;overflow:hidden;">' +
            '<div id="pfi-progress-bar" style="height:100%;width:0%;background:#4a90d9;border-radius:4px;transition:width 0.4s ease;"></div>' +
          '</div>' +
        '</div>'
      );

      $dialog
        .append('<div class="' + myId + '_working_bg" style="display: none;"></div>')
        .append('<div class="' + myId + '_working" style="display: none;"></div>');
    })
  };
};

var printObj = new PrintPDF(options);
if (this instanceof HTMLElement) {
  $(this).data('printObj', printObj);
}
