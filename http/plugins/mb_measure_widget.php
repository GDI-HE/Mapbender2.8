/**
 * Package: measure_widget
 *
 * Description:
 * Measure module with jQuery UI widget factory and RaphaelJS
 *
 * Files:
 *  - http/plugins/mb_measure_widget.js
 *  - http/widgets/w_measure.js
 *
 * SQL:
 * > INSERT INTO gui_element(fkey_gui_id, e_id, e_pos, e_public, e_comment, 
 * > e_title, e_element, e_src, e_attributes, e_left, e_top, e_width, e_height, 
 * > e_z_index, e_more_styles, e_content, e_closetag, e_js_file, e_mb_mod, e_target, 
 * > e_requires, e_url) VALUES('<appId>','measure_widget',2,1,'Measure',
 * > 'Measure distance','img','../img/button_blue_red/measure_off.png','',
 * > NULL ,NULL ,NULL ,NULL ,1,'','','','../plugins/mb_measure_widget.php',
 * > '../widgets/w_measure.js,../extensions/RaphaelJS/raphael-1.4.7.min.js',
 * > 'mapframe1','jq_ui_dialog,jq_ui_widget','http://www.mapbender.org/index.php/Measure');
 * > 
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'lineStrokeDefault', '#808080', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'lineStrokeSnapped', '#F30', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'lineStrokeWidthDefault', '2', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'lineStrokeWidthSnapped', '2', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'measurePointDiameter', '7', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'opacity', '0.5', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'pointFillDefault', '#B2DFEE', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'pointFillSnapped', '#FF0000', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'pointStrokeDefault', '#FF0000', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'pointStrokeSnapped', '#FF0000', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'pointStrokeWidthDefault', '2', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'polygonFillDefault', '#B2DFEE', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'polygonFillSnapped', '#FC3', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'polygonStrokeWidthDefault', '1', '' ,'var');
 * > INSERT INTO gui_element_vars(fkey_gui_id, fkey_e_id, var_name, var_value, context, var_type)
 * > VALUES('<app_id>', 'measure_widget', 'polygonStrokeWidthSnapped', '3', '' ,'var');
 *
 * Help:
 * http://www.mapbender.org/Measure_widget
 *
 * Maintainer:
 * http://www.mapbender.org/User:Christoph_Baudson
 *
 * License:
 * Copyright (c) 2009, Open Source Geospatial Foundation
 * This program is dual licensed under the GNU General Public License
 * and Simplified BSD license.
 * http://svn.osgeo.org/mapbender/trunk/mapbender/license/license.txt
 */

var $measure = $(this);

var MeasureApi = function (o) {

	var measureDialog,
		button,
		that = this,
		origEnableFeatureInfo = null,
		inProgress = false,
		title = o.title,
		defaultHtml = "<div title='" + title + "'>" +
			"<div class='mb-measure-text'><?php 
				echo nl2br(htmlentities(_mb("Click in the map to start measuring."), ENT_QUOTES, "UTF-8"));
			?></div></div>",
		informationHtml =
			"<div class='mb-measure-prompt' style='color:#555; font-size:12px; margin-bottom:8px; line-height:1.4; display:none;'><?php
				echo nl2br(htmlentities(_mb("Click in the map to start measuring."), ENT_QUOTES, "UTF-8"));
			?></div>" +
			"<div><?php
				echo nl2br(htmlentities(_mb("Last point: "), ENT_QUOTES, "UTF-8"));
			?><span class='mb-measure-clicked-point' /></div>" +
			"<div><?php
				echo nl2br(htmlentities(_mb("Current point: "), ENT_QUOTES, "UTF-8"));
			?><span class='mb-measure-current-point' /></div>" +
			"<div><?php
				echo nl2br(htmlentities(_mb("Distance (to last point): "), ENT_QUOTES, "UTF-8"));
			?><span class='mb-measure-distance-last' />  <span class='mb-measure-distance-last-unit' /></div>" +
			"<div><?php
				echo nl2br(htmlentities(_mb("Distance (total): "), ENT_QUOTES, "UTF-8"));
			?><span class='mb-measure-distance-total' /> <span class='mb-measure-distance-total-unit' /></div>" +
			"<div><?php
				echo nl2br(htmlentities(_mb("Perimeter: "), ENT_QUOTES, "UTF-8"));
			?><span class='mb-measure-perimeter' /> <span class='mb-measure-perimeter-unit' /></div>" +
			"<div><?php
				echo nl2br(htmlentities(_mb("Area: "), ENT_QUOTES, "UTF-8"));
			?><span class='mb-measure-area' /> <span class='mb-measure-area-unit' /></div>" +
                        "<div><?php
				echo nl2br(htmlentities(_mb("Angle: "), ENT_QUOTES, "UTF-8"));
			?><span class='mb-measure-angle' /> <span class='mb-measure-angle-unit' /></div>" +
			"<div class='mb-measure-actions' style='margin-top:10px; padding-top:8px; border-top:1px solid #e2e8f0; display:none;'>" +
				"<div style='display:flex; justify-content:space-between; align-items:center; gap:8px;'>" +
					"<button type='button' class='mb-measure-add-btn' style='flex:1; cursor:pointer; padding:5px 10px; background:#f0f4f9; color:#1a5fb4; border:1px solid #c2d7ef; border-radius:4px; font-size:11px; font-weight:600; line-height:1.2; text-align:center;'>+ <?php echo htmlentities(_mb("Add measurement"), ENT_QUOTES, "UTF-8"); ?></button>" +
					"<button type='button' class='mb-measure-clear-btn' style='cursor:pointer; padding:5px 10px; background:#fff; color:#d93025; border:1px solid #f1aeb5; border-radius:4px; font-size:11px; font-weight:500; line-height:1.2; text-align:center;'><?php echo htmlentities(_mb("Delete all"), ENT_QUOTES, "UTF-8"); ?></button>" +
				"</div>" +
			"</div>" +
			"<div class='mb-measure-history' style='margin-top:8px; max-height:150px; overflow-y:auto;'></div>";

	var hideMeasureData = function () {
		measureDialog.find(".mb-measure-clicked-point").parent().hide();
		measureDialog.find(".mb-measure-current-point").parent().hide();
		measureDialog.find(".mb-measure-distance-last").parent().hide();
		measureDialog.find(".mb-measure-distance-total").parent().hide();
		measureDialog.find(".mb-measure-perimeter").parent().hide();
		measureDialog.find(".mb-measure-area").parent().hide();
                measureDialog.find(".mb-measure-angle").parent().hide();

	};

	var syncPrintCoordinates = function () {
		var allMeasures = [];
		if (o.$target.size() > 0 && o.$target.data("mb_measure")) {
			allMeasures = o.$target.mb_measure("getMeasures");
		}
		var xs = [], ys = [];
		if (allMeasures && allMeasures.length > 0) {
			for (var m = 0; m < allMeasures.length; m++) {
				var pts = allMeasures[m].points;
				for (var p = 0; p < pts.length; p++) {
					xs.push(pts[p].pos.x);
					ys.push(pts[p].pos.y);
				}
			}
		}
		$('input[name="measured_x_values"]').val(xs.join(","));
		$('input[name="measured_y_values"]').val(ys.join(","));
	};

	var renderMeasureHistory = function (measures) {
		if (!measures && o.$target.size() > 0 && o.$target.data("mb_measure")) {
			measures = o.$target.mb_measure("getMeasures");
		}
		var $hist = measureDialog.find(".mb-measure-history");
		$hist.empty();
		if (!measures || measures.length === 0) {
			measureDialog.find(".mb-measure-prompt").show();
			measureDialog.find(".mb-measure-actions").show();
			measureDialog.find(".mb-measure-add-btn").show();
			measureDialog.find(".mb-measure-clear-btn").hide();
			return;
		}
		measureDialog.find(".mb-measure-prompt").hide();
		measureDialog.find(".mb-measure-actions").show();
		measureDialog.find(".mb-measure-add-btn").show();
		measureDialog.find(".mb-measure-clear-btn").show();
		for (var i = 0; i < measures.length; i++) {
			(function (m, idx) {
				var $row = $("<div style='display:flex; justify-content:space-between; align-items:center; padding:4px 6px; margin:2px 0; border-radius:4px; border:1px solid #f0f0f0; background:#fafafa; font-size:11px;' />");
				$row.hover(
					function () { $(this).css("background", "#f0f4f9"); },
					function () { $(this).css("background", "#fafafa"); }
				);
				var labelText = "#" + (idx + 1) + " " + m.title + ": " + m.badgeText;
				var $link = $("<a href='javascript:void(0);' style='color:#1a5fb4; text-decoration:none; font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; max-width:215px;' />")
					.text(labelText)
					.attr("title", "<?php echo htmlentities(_mb("Click to select and zoom"), ENT_QUOTES, "UTF-8"); ?>")
					.bind("click", function () {
						if (o.$target.size() > 0 && o.$target.data("mb_measure")) {
							o.$target.mb_measure("selectMeasure", m.id);
							o.$target.mb_measure("centerOnMeasure", m.id);
						}
					});
				var $del = $("<a href='javascript:void(0);' style='color:#d93025; font-size:18px; line-height:1; text-decoration:none; margin-left:8px; padding:2px 6px; border-radius:4px; display:inline-flex; align-items:center; justify-content:center; cursor:pointer;' title='<?php echo htmlentities(_mb("Delete"), ENT_QUOTES, "UTF-8"); ?>'>&#128465;</a>")
					.hover(
						function () { $(this).css({"background": "#fee2e2", "color": "#b91c1c"}); },
						function () { $(this).css({"background": "transparent", "color": "#d93025"}); }
					)
					.bind("click", function (e) {
						e.stopPropagation();
						if (o.$target.size() > 0 && o.$target.data("mb_measure")) {
							var remaining = o.$target.mb_measure("deleteMeasure", m.id);
							renderMeasureHistory(remaining);
							syncPrintCoordinates();
						}
					});
				$row.append($link).append($del);
				$hist.append($row);
			})(measures[i], i);
		}
	};

	var bindDialogButtons = function () {
		measureDialog.find(".mb-measure-add-btn").hover(
			function () { $(this).css({"background": "#e2ecf7", "border-color": "#9ec5fe"}); },
			function () { $(this).css({"background": "#f0f4f9", "border-color": "#c2d7ef"}); }
		).unbind("click").bind("click", function () {
			// Suppress FeatureInfo when starting another measurement
			if (typeof Mapbender !== "undefined" && typeof Mapbender.disableFeatureInfo === "function") {
				Mapbender.disableFeatureInfo();
			}
			// Make measurement tool active
			Mapbender.bindPanEvents();
			$('#measure_widget').addClass('myOnClass');
			if (o.$target.size() > 0 && o.$target.data("mb_measure")) {
				o.$target.mb_measure("startNewMeasure");
			} else {
				that.activate();
			}
			hideMeasureData();
			measureDialog.find(".mb-measure-prompt").show();
		});
		measureDialog.find(".mb-measure-clear-btn").hover(
			function () { $(this).css({"background": "#fee2e2", "border-color": "#f87171", "color": "#b91c1c"}); },
			function () { $(this).css({"background": "#fff", "border-color": "#f1aeb5", "color": "#d93025"}); }
		).unbind("click").bind("click", function () {
			if (o.$target.size() > 0 && o.$target.data("mb_measure")) {
				o.$target.mb_measure("clearAllMeasures");
				renderMeasureHistory([]);
				syncPrintCoordinates();
			}
			hideMeasureData();
		});
	};

	var changeDialogContent = function () {
		if (measureDialog.find(".mb-measure-actions").length === 0) {
			measureDialog.html(informationHtml);
			bindDialogButtons();
		}
		hideMeasureData();
		renderMeasureHistory();
	};

	var create = function () {
		//
		// Initialise measure dialog
		//
		measureDialog = $(defaultHtml);
		measureDialog.dialog({
			width: 350,
			autoOpen: false,
			position: [o.$target.offset().left+20, o.$target.offset().top+80],
			open: function() {
				$('#toolsContainer').hide();
				$('a.toggleToolsContainer').removeClass('activeToggle');
				// Enable panning during measurement and suppress FeatureInfo
				Mapbender.bindPanEvents();
				Mapbender.disableFeatureInfo();
				// Guard Mapbender.enableFeatureInfo while measurement is active so external callers (e.g. mod_changeEPSG) cannot prematurely re-enable FeatureInfo
				if (!origEnableFeatureInfo && typeof Mapbender !== "undefined" && typeof Mapbender.enableFeatureInfo === "function") {
					origEnableFeatureInfo = Mapbender.enableFeatureInfo;
					Mapbender.enableFeatureInfo = function() {
						if ($('#measure_widget').hasClass('myOnClass') || (o.$target && o.$target.data('mb_measure'))) {
							return;
						}
						if (origEnableFeatureInfo) {
							origEnableFeatureInfo.apply(this, arguments);
						}
					};
				}
			},
			close: function (){
				$('#measure_widget').removeClass('myOnClass');
				// Restore original enableFeatureInfo and re-enable FeatureInfo after measurement closes
				if (origEnableFeatureInfo && typeof Mapbender !== "undefined") {
					Mapbender.enableFeatureInfo = origEnableFeatureInfo;
					origEnableFeatureInfo = null;
				}
				Mapbender.enableFeatureInfo();
				mb_enableButton('pan1');				
			}
		}).bind("dialogclose", function () {
			button.stop();
			that.destroy();
			// Restore original enableFeatureInfo and re-enable FeatureInfo on dialog close
			if (origEnableFeatureInfo && typeof Mapbender !== "undefined") {
				Mapbender.enableFeatureInfo = origEnableFeatureInfo;
				origEnableFeatureInfo = null;
			}
			Mapbender.enableFeatureInfo();
			mb_enableButton('pan1');
		});

		//
		// Initialise button
		//
		button = new Mapbender.Button({
			domElement: $measure.get(0),
			over: o.src.replace(/_off/, "_over"),
			on: o.src.replace(/_off/, "_on"),
			off: o.src,
			name: o.id,
			go: that.activate,
			stop: that.deactivate
		});
	};

	var updateCurrentPoint = function (evt, data) {
		if (data.pos) {
			var p = data.pos;
			measureDialog.find(".mb-measure-current-point").text(
				p.pos.x + " " + p.pos.y
			).parent().show();
		}
	};

	var updateClickedPoint = function (evt, data) {
		measureDialog.find(".mb-measure-prompt").hide();
		measureDialog.find(".mb-measure-actions").hide();
		if (data.pos) {
			var p = data.pos;
			measureDialog.find(".mb-measure-clicked-point").text(
				p.pos.x + " " + p.pos.y
			).parent().show();


                        var measuredX = $('input[name="measured_x_values"]').val();
                        if(measuredX != "") {
                            measuredX += ",";
		}
                        measuredX += p.pos.x;
                        $('input[name="measured_x_values"]').val(measuredX)

                        var measuredY = $('input[name="measured_y_values"]').val();
                        if(measuredY != "") {
                            measuredY += ",";
                        }
                        measuredY += p.pos.y;
                        $('input[name="measured_y_values"]').val(measuredY);
		}
	};

	var updateCurrentDistance = function (evt, data) {
		if (data.currentDistance) {
			var lastDistanceUnit = "m";
			var displayDistance = data.currentDistance;
			if (displayDistance > 10000){
				displayDistance /= 1000;
				lastDistanceUnit = "km";
			}
			measureDialog.find(".mb-measure-distance-last-unit").html(lastDistanceUnit);
			measureDialog.find(".mb-measure-distance-last").text(Math.round(displayDistance*10)/10).parent().show();
		}
	};

	var updateTotalDistance = function (evt, data) {
		if (data.totalDistance) {
			var totalDistanceUnit = "m";
			var displayTotalDistance = data.totalDistance;
			if (displayTotalDistance > 10000){
				displayTotalDistance = displayTotalDistance / 1000;
				totalDistanceUnit = "km";
			}
			measureDialog.find(".mb-measure-distance-total-unit").html(totalDistanceUnit);
			measureDialog.find(".mb-measure-distance-total").text(Math.round(displayTotalDistance*10)/10).parent().show();
		}
		else {
			measureDialog.find(".mb-measure-distance-total").parent().hide();
		}
	};

	var updatePerimeter = function (evt, data) {
		if (data.perimeter) {
			var unit = "m";
			var displayPerimeter = data.perimeter;
			if (displayPerimeter > 10000){
				displayPerimeter = displayPerimeter / 1000;
				unit = "km";
			}
			measureDialog.find(".mb-measure-perimeter-unit").html(unit);
			measureDialog.find(".mb-measure-perimeter").text(Math.round(displayPerimeter*10)/10).parent().show();

		}
		else {
			//measureDialog.find(".mb-measure-perimeter").parent().hide();
		}
	};

	var updateArea = function (evt, data) {
		if (data.area) {
			var areaUnit = "m&sup2;";
			var area = data.area;
			if (area > 10000000){
				area /= 1000000;
				areaUnit = "km&sup2;";
			}
			else if (area > 100000){
				area /= 10000;
				areaUnit = "ha";
			}
			area = Math.round(area*10)/10;

			measureDialog.find(".mb-measure-area-unit").html(areaUnit);
			measureDialog.find(".mb-measure-area").text(area).parent().show();
		}
		else {
			measureDialog.find(".mb-measure-area").parent().hide();
		}
	};

        var updateAngle = function (evt, data) {
        	if (data.currentAngle) {
			var unit = "°";
			var displayAngle = data.currentAngle;
                        measureDialog.find(".mb-measure-angle-unit").html(unit);
			measureDialog.find(".mb-measure-angle").text(Math.round(displayAngle*10)/10).parent().show();

		}
		else {
			//measureDialog.find(".mb-measure-angle").parent().hide();
		}
	};

	var updateView = function (evt, data) {
		updateCurrentPoint(evt, data);
		updateCurrentDistance(evt, data);
		updateTotalDistance(evt, data);
		updateArea(evt, data);
		updatePerimeter(evt, data);
                updateAngle(evt, data);
	};

	var finishMeasure = function (evt, data) {
		inProgress = false;
		if (measureDialog.find(".mb-measure-actions").length === 0) {
			measureDialog.html(informationHtml);
			bindDialogButtons();
		}
		hideMeasureData();
		renderMeasureHistory(data ? data.allMeasures : null);
		syncPrintCoordinates();
	};

	var reinitializeMeasure = function () {
		inProgress = false;
		that.deactivate();
		that.activate();
	};

	this.activate = function () {
		//remove measured x and y values from print dialog
		$('input[name="measured_x_values"]').val("");
		$('input[name="measured_y_values"]').val("");

		if (o.$target.size() > 0) {
			o.$target
				.mb_measure(o)
				.bind("mb_measureupdate", updateView)
				.bind("mb_measurepointadded", updateClickedPoint)
				.bind("mb_measurelastpointadded", finishMeasure)
				.bind("mb_measuredeleted", function (evt, data) {
					renderMeasureHistory(data ? data.allMeasures : null);
					syncPrintCoordinates();
				})
				.bind("mb_measureallcleared", function () {
					renderMeasureHistory([]);
					syncPrintCoordinates();
				})
				.bind("mb_measuresrschanged", function (evt, data) {
					// Maintain FeatureInfo disabled state across SRS changes while measurement is active
					if (typeof Mapbender !== "undefined" && typeof Mapbender.disableFeatureInfo === "function") {
						Mapbender.disableFeatureInfo();
					}
					// Update measurement history display and sync print coordinates when coordinate reference system (SRS/EPSG) changes
					renderMeasureHistory(data ? data.allMeasures : null);
					syncPrintCoordinates();
				})
				.bind("mb_measurereinitialize", reinitializeMeasure)
				.bind("click", changeDialogContent);
		}
		if (!inProgress) {
			inProgress = true;
			measureDialog.html(informationHtml);
			bindDialogButtons();
			hideMeasureData();
			renderMeasureHistory([]);
		}

		measureDialog.dialog("open");
		
	};

	this.destroy = function () {
		if (o.$target.size() > 0) {
			o.$target.mb_measure("destroy")
				.unbind("mb_measureupdate", updateView)
				.unbind("mb_measurepointadded", updateClickedPoint)
				.unbind("mb_measurelastpointadded", finishMeasure)
				.unbind("mb_measuredeleted")
				.unbind("mb_measureallcleared")
				.unbind("mb_measuresrschanged")
				.unbind("mb_measurereinitialize", reinitializeMeasure)
				.unbind("click", changeDialogContent);
		}
		hideMeasureData();

		if (measureDialog.dialog("isOpen")) {
			measureDialog.dialog("close");
		}
		measureDialog.html(defaultHtml);

		if (origEnableFeatureInfo && typeof Mapbender !== "undefined") {
			Mapbender.enableFeatureInfo = origEnableFeatureInfo;
			origEnableFeatureInfo = null;
		}
		//remove measured x and y values from print dialog
		$('input[name="measured_x_values"]').val("");
		$('input[name="measured_y_values"]').val("");
	};
	
	this.deactivate = function () {
		if (o.$target.size() > 0) {
			o.$target.mb_measure("deactivate");
		}
	};

	create();
};

$measure.mapbender(new MeasureApi(options));
