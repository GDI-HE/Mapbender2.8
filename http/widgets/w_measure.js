$.widget("mapbender.mb_measure", {
	options: {
		opacity: 0.6,
		measurePointDiameter: 10,
		measurePointDiameterSnapped: 15,
		polygonFillSnapped: "#FC3",
		polygonFillDefault: "#FFF",
		polygonStrokeWidthDefault: 1,
		polygonStrokeWidthSnapped: 5,
		lineStrokeDefault: "#C9F",
		lineStrokeSnapped: "#F30",
		lineStrokeWidthDefault: 3,
		lineStrokeWidthSnapped: 5,
		pointFillSnapped: "#F90",
		pointFillDefault: "#CCF",
		pointStrokeDefault: "#FC3",
		pointStrokeSnapped: "#F30",
		pointStrokeWidthDefault: 2
	},
	_measurePoints: [],
	_completedMeasures: [],
	_measureCounter: 0,
	_selectedMeasureId: null,
	_$overlay: null,
	_map: undefined,
	_srs: undefined,
	_currentDistance: 0,
        _currentAngle: 0,
	_totalDistance: 0,
	_polygonIsInvalid: false,
	_currentPolygonIsInvalid: false,
	_canvas: undefined,
	_isPolygon: function (pos) {

		var len = this._measurePoints.length;

		var max = pos ? len - 1 : len - 2;

		if (pos && len < 2 || !pos && len < 3) {
			return false;
		}

		var posLocal = pos ? pos : this._measurePoints[len - 1];

		var p0 = this._measurePoints[0].pos;
		var pn = this._measurePoints[max].pos;
		for (var i = 0; i < max; i++)  {
			var pi = this._measurePoints[i].pos;
			var pj = this._measurePoints[i + 1].pos;

			if (i > 0 && this._lineIntersect(
				pi.x, pi.y, pj.x, pj.y,
				p0.x, p0.y, posLocal.x, posLocal.y)
			) {
				return false;
			}
			if (this._lineIntersect(
				pi.x, pi.y, pj.x, pj.y,
				pn.x, pn.y, posLocal.x, posLocal.y)
			) {
				this._currentPolygonIsInvalid = true;
				return false;
			}
		}
		this._currentPolygonIsInvalid = false;
		return true;
	},
	_lineIntersect: function ( x1, y1, x2, y2, x3, y3, x4, y4 ) {
		var isOnSegment = function (xi, yi, xj, yj, xk, yk) {
			// changed <= to < so the segments are allowed to touch!
			 return (xi < xk || xj < xk) &&
					(xk < xi || xk < xj) &&
			        (yi < yk || yj < yk) &&
					(yk < yi || xk < yj);
		};

		var computeDirection = function (xi, yi, xj, yj, xk, yk) {
			var a = (xk - xi) * (yj - yi);
			var b = (xj - xi) * (yk - yi);
			return a < b ? -1 : a > b ? 1 : 0;
		};

		var e1 = computeDirection(x3, y3, x4, y4, x1, y1);
		var e2 = computeDirection(x3, y3, x4, y4, x2, y2);
		var e3 = computeDirection(x1, y1, x2, y2, x3, y3);
		var e4 = computeDirection(x1, y1, x2, y2, x4, y4);
		return (((e1 > 0 && e2 < 0) || (e1 < 0 && e2 > 0)) &&
			((e3 > 0 && e4 < 0) || (e3 < 0 && e4 > 0))) ||
			(e1 === 0 && isOnSegment(x3, y3, x4, y4, x1, y1)) ||
			(e2 === 0 && isOnSegment(x3, y3, x4, y4, x2, y2)) ||
			(e3 === 0 && isOnSegment(x1, y1, x2, y2, x3, y3)) ||
			(e4 === 0 && isOnSegment(x1, y1, x2, y2, x4, y4));
	},
	_toRad: function (deg) {
		return deg * Math.PI/180;
	},
	//
	// calculate area
	//
	_calculateAreaMetric: function (pos) {
		if (this._measurePoints.length < 2) {
			return null;
		}
		this._measurePoints.push(pos.pos ? pos : { pos: pos });
		var part, area = 0;
		var p0 = this._measurePoints[0].pos, pi, pj;
		for (var i = 0; i < this._measurePoints.length - 1; i++)  {
			pi = this._measurePoints[i].pos;
			pj = this._measurePoints[i + 1].pos;
			part = (pi.y + pj.y) * (pi.x - pj.x) / 2;
			area += part;
		}
		part = (pj.y + p0.y) * (pj.x - p0.x) / 2;
		area += part;
		this._measurePoints.pop();
		return Math.abs(area);
	},
	_calculateAreaGeographic: function (pos) {
		if (this._measurePoints.length < 2) {
			return null;
		}

		// add current point and first point
		this._measurePoints.push(pos.pos ? pos : { pos: pos });
		this._measurePoints.push(this._measurePoints[0]);

        var area = 0.0;
        var p1, p2;
        for(var i=0; i<this._measurePoints.length-1; i++) {
            p1 = this._measurePoints[i].pos;
            p2 = this._measurePoints[i+1].pos;
			var c = this._toRad(p2.x - p1.x) *
                    (2 + Math.sin(this._toRad(p1.y)) +
                    Math.sin(this._toRad(p2.y)));
            area += c;
        }
        area = area * 6378137.0 * 6378137.0 / 2.0;

		// remove current point and first point
		this._measurePoints.pop();
		this._measurePoints.pop();

		return Math.abs(area);
	},
	_calculateArea: function (pos) {
		switch (this._map.getSrs()) {
			case "EPSG:4326":
				return this._calculateAreaGeographic(pos);
			default:
				return this._calculateAreaMetric(pos);
		}
		return null;
	},
	//
	// calculate distance
	//
	_calculateDistanceGeographic: function (a, b) {
		var lon_from = this._toRad(a.x);
		var lat_from = this._toRad(a.y);
		var lon_to = this._toRad(b.x);
		var lat_to = this._toRad(b.y);
		var cosVal = Math.sin(lat_from) * Math.sin(lat_to) +
			Math.cos(lat_from) * Math.cos(lat_to) *
			Math.cos(lon_from - lon_to);
		cosVal = Math.max(-1, Math.min(1, cosVal));
		return Math.abs(6371229 * Math.acos(cosVal));
	},
	_calculateDistanceMetric: function (a, b) {
		return Math.abs(Math.sqrt(
			Math.pow(Math.abs(b.x - a.x), 2) +
			Math.pow(Math.abs(b.y - a.y), 2)
		));
	},
	_calculateDistance: function (a, b) {
		if (a !== null && b !== null) {
			switch (this._map.getSrs()) {
				case "EPSG:4326":
					return this._calculateDistanceGeographic(a, b);
				default:
					return this._calculateDistanceMetric(a, b);
			}
		}
		return null;
	},
        _calculateAngle: function (a, b, c) {
		if (a !== null && b !== null && c !== null) {
                        function angleAt(a,b,c){
                            var vectorAB = [b.x- a.x,b.y-a.y];
                            //var vectorBC = [c.x- b.x,c.y-b.y];
                            var vectorBC = [b.x- c.x,b.y-c.y];
                            var cosalpha = scalarProduct(vectorAB,vectorBC)/
                                (vectorlen(vectorAB)*vectorlen(vectorBC));

                            var acosalpha = Math.acos(cosalpha);
                            return (acosalpha/Math.PI)*180;
                        }

                        function scalarProduct(va,vb){
                                return va[0]*vb[0] + va[1]*vb[1];
                        }

                        function vectorlen(v) {
                                return  Math.sqrt(v[0]*v[0] + v[1]*v[1]);
                        }

                        var angle = angleAt(a,b,c);
                        return angle;
		}
		return null;
	},
	_formatMeasure: function (val, isArea, isShort) {
		if (!val && val !== 0) return "";
		if (isArea) {
			var km2 = Math.round(val / 1e6 * 100) / 100;
			var ha = Math.round(val / 1e4 * 100) / 100;
			var km2Str = km2.toLocaleString("de-DE");
			var m2Str = Math.round(val).toLocaleString("de-DE");
			if (isShort) return (val >= 1e6 ? km2Str + " km²" : m2Str + " m²");
			if (val >= 1e6) return m2Str + " m² (" + km2Str + " km²)";
			return m2Str + " m² (" + ha.toLocaleString("de-DE") + " ha)";
		}
		var km = Math.round(val / 1000 * 100) / 100;
		var kmStr = km.toLocaleString("de-DE");
		var mRound = Math.round(val * 10) / 10;
		var mStr = mRound.toLocaleString("de-DE");
		if (isShort) return (val >= 1000 ? (Math.round(val / 1000 * 10) / 10).toLocaleString("de-DE") + " km" : mStr + " m");
		if (val >= 1000) return mStr + " m (" + kmStr + " km)";
		return mStr + " m";
	},
	_calculateCentroid: function (points) {
		var sx = 0, sy = 0;
		for (var k = 0; k < points.length; k++) {
			sx += points[k].pos.x;
			sy += points[k].pos.y;
		}
		return { x: sx / points.length, y: sy / points.length };
	},
	_isPointInPolygon: function (px, py, points) {
		if (!points || points.length < 3) return false;
		var inside = false;
		for (var i = 0, j = points.length - 1; i < points.length; j = i++) {
			var xi = points[i].mousePos ? points[i].mousePos.x : 0, yi = points[i].mousePos ? points[i].mousePos.y : 0;
			var xj = points[j].mousePos ? points[j].mousePos.x : 0, yj = points[j].mousePos ? points[j].mousePos.y : 0;
			if (((yi > py) !== (yj > py)) && (px < (xj - xi) * (py - yi) / (yj - yi) + xi)) inside = !inside;
		}
		return inside;
	},
	_isPointNearLine: function (px, py, points, tol, isClosed) {
		if (!points || points.length < 2) return false;
		var tolSq = (tol || 8) * (tol || 8);
		var count = isClosed ? points.length : points.length - 1;
		for (var i = 0; i < count; i++) {
			var p1 = points[i].mousePos, p2 = points[(i + 1) % points.length].mousePos;
			if (p1 && p2) {
				var dx = p2.x - p1.x, dy = p2.y - p1.y, l2 = dx * dx + dy * dy;
				var t = l2 === 0 ? 0 : Math.max(0, Math.min(1, ((px - p1.x) * dx + (py - p1.y) * dy) / l2));
				var qx = p1.x + t * dx, qy = p1.y + t * dy;
				var rx = px - qx, ry = py - qy;
				if (rx * rx + ry * ry <= tolSq) return true;
			}
		}
		return false;
	},
	_findMeasureAt: function (mousePos) {
		if (!this._completedMeasures || this._completedMeasures.length === 0) return null;
		for (var i = this._completedMeasures.length - 1; i >= 0; i--) {
			var m = this._completedMeasures[i];
			// Only select polygon if clicking directly near its boundary edge (allow drawing new measurements freely inside)
			if (m.type === "polygon" && this._isPointNearLine(mousePos.x, mousePos.y, m.points, 6, true)) return m;
			if (m.type === "line" && this._isPointNearLine(mousePos.x, mousePos.y, m.points, 8, false)) return m;
			if (m.type === "point" && m.points[0].mousePos && mousePos.dist(m.points[0].mousePos) <= 10) return m;
		}
		return null;
	},
	_isPointSnapped: function (p1, p2) {
		if (!p1 || !p2 || typeof p1.dist !== "function") return false;
		var radius = Math.max(10, (this.options.measurePointDiameter || 7));
		return p1.dist(p2) <= radius;
	},
	_isFirstPointSnapped: function (p) {
		if (this._measurePoints.length > 0 ) {
			var pos0 = this._measurePoints[0].mousePos;
			if (this._measurePoints.length > 2 && this._isPointSnapped(pos0, p)) {
				return true;
			}
		}
		return false;
	},
	_isLastPointSnapped: function (p) {
		if (this._measurePoints.length > 0 ) {
			var posn = this._measurePoints[this._measurePoints.length - 1].mousePos;
			if (this._measurePoints.length >= 1 && this._isPointSnapped(posn, p)) {
				return true;
			}
		}
		return false;
	},
	_draw: function (pos, drawOptions) {
		this._canvas.clear();

		// 1. Draw all completed measurements
		if (this._completedMeasures && this._completedMeasures.length > 0) {
			for (var mIdx = 0; mIdx < this._completedMeasures.length; mIdx++) {
				var m = this._completedMeasures[mIdx];
				var isSel = (m.id === this._selectedMeasureId || (this._activeSnap && this._activeSnap.measure.id === m.id) || (this._hoveredMeasure && this._hoveredMeasure.id === m.id));
				var mPath = "";
				for (var pIdx = 0; pIdx < m.points.length; pIdx++) {
					var pt = m.points[pIdx];
					pt.mousePos = this._map.convertRealToPixel(pt.pos);
					mPath += (pIdx === 0 ? "M" : "L") + pt.mousePos.x + " " + pt.mousePos.y;
				}

				if (m.type === "polygon") {
					var poly = this._canvas.path(mPath + "Z");
					poly.attr({
						fill: (drawOptions && drawOptions.isPrinting) ? "none" : (isSel ? "#ffa500" : (this.options.polygonFillDefault || "#ffaa00")),
						"fill-opacity": (drawOptions && drawOptions.isPrinting) ? 0 : (isSel ? 0.45 : (this.options.opacity || 0.35)),
						stroke: isSel ? "#e65100" : (this.options.lineStrokeDefault || "#ea6c00"),
						"stroke-width": isSel ? 4 : (this.options.lineStrokeWidthDefault || 3)
					});
				} else if (m.type === "line") {
					var line = this._canvas.path(mPath);
					line.attr({
						stroke: isSel ? "#e65100" : (this.options.lineStrokeDefault || "#ea6c00"),
						"stroke-width": isSel ? 4 : (this.options.lineStrokeWidthDefault || 3)
					});
				}

				// Draw vertex circles for completed measurement, highlighting endpoints
				for (var pIdx = 0; pIdx < m.points.length; pIdx++) {
					var pt = m.points[pIdx];
					var isEnd = (pIdx === 0 || pIdx === m.points.length - 1);
					var c = this._canvas.circle(pt.mousePos.x, pt.mousePos.y, isEnd ? (isSel ? 5.5 : 5) : (isSel ? 4.5 : 3.5));
					c.attr({
						fill: isEnd ? (isSel ? "#e65100" : "#ff7f00") : (isSel ? "#ff9800" : "#ffa726"),
						stroke: "#ffffff",
						"stroke-width": isEnd ? 2 : (isSel ? 1.5 : 1)
					});
				}

				// Draw measurement info badge in SVG only when exporting for print
				if (drawOptions && drawOptions.isPrinting && m.badgeText) {
					var bPixel = this._map.convertRealToPixel(m.centerPos);
					var bX = bPixel.x, bY = (m.type === "point") ? bPixel.y - 14 : bPixel.y;
					var bw = Math.max(44, m.badgeText.length * 6.2 + 12), bh = 16;
					this._canvas.rect(bX - bw / 2, bY - bh / 2, bw, bh, 3).attr({
						fill: "#ff7f00", stroke: "#cc6600", "stroke-width": 1
					}).toFront();
					this._canvas.text(bX, bY, m.badgeText).attr({
						fill: "#ffffff", "font-family": "Arial, Helvetica, sans-serif",
						"font-size": 10, "font-weight": "bold", "text-anchor": "middle"
					}).toFront();
				}
			}
		}

		// 2. Draw active in-progress geometry (skip if printing)
		if (drawOptions && drawOptions.isPrinting) {
			this._updateOverlays();
			return;
		}

		var str_path = "";

		if (pos && drawOptions && !drawOptions.highlightFirst) {
			this._measurePoints.push(pos);
		}

		var len = this._measurePoints.length;
		if (len > 0) {
			for (var k=0; k < len; k++) {
				var pk = this._measurePoints[k].pos;
				var q = this._measurePoints[k].mousePos;

				str_path += (k === 0) ? 'M' : 'L';
				str_path += q.x + ' ' + q.y;

				if (drawOptions && drawOptions.highlightFirst && k === len - 1) {
					var p0 = this._measurePoints[0].mousePos;
					str_path += 'L' + p0.x + ' ' + p0.y;
				}
				if (drawOptions && drawOptions.highlightLast && k === len - 1) {
					continue;
				}

				if (drawOptions && drawOptions.drawPoints &&
					(k === 0 && !this._polygonIsInvalid || k >= len - 2 && !drawOptions.highlightFirst)) {

					var isSnapped = drawOptions && (
						(k === 0 && (drawOptions.highlightFirst || (drawOptions.highlightLast && len === 2))) ||
						(drawOptions.highlightLast && k === len - 2)
					);
					var isPointSnap = drawOptions && drawOptions.highlightLast && len === 2 && k === 0;
					var radius = isPointSnap ? (this.options.measurePointDiameterSnapped || 15) : (this.options.measurePointDiameter || 10);
					var circle = this._canvas.circle(q.x, q.y, radius);

					circle.attr({
						fill: isSnapped ? this.options.pointFillSnapped : this.options.pointFillDefault,
						"fill-opacity": this.options.opacity,
						stroke: isSnapped ? this.options.pointStrokeSnapped : this.options.pointStrokeDefault,
						"stroke-width": this.options.pointStrokeWidthDefault
					});
				}
			}
		}
		if (pos && drawOptions && !drawOptions.highlightFirst) {
			this._measurePoints.pop();
		}

		if (this._isPolygon(this._measurePoints, pos) && drawOptions && !drawOptions.highlightLast && !this._polygonIsInvalid) {
			var poly = this._canvas.path(str_path + 'Z');
			poly.attr({
				fill: drawOptions.highlightFirst ?
					this.options.polygonFillSnapped : this.options.polygonFillDefault,
				stroke: drawOptions.highlightFirst ?
					this.options.lineStrokeSnapped : "none",
				"stroke-width": drawOptions.highlightFirst ?
					this.options.polygonStrokeWidthSnapped : 0,
				opacity: this.options.opacity
			});
		}

		if (str_path.length > 0) {
			var activeLine = this._canvas.path(str_path);
			activeLine.attr({
				stroke: drawOptions && (drawOptions.highlightFirst || drawOptions.highlightLast) ?
					this.options.lineStrokeSnapped : this.options.lineStrokeDefault,
				"stroke-width": drawOptions && drawOptions.highlightLast ?
					this.options.lineStrokeWidthSnapped : this.options.lineStrokeWidthDefault
			});
			activeLine.toFront();
		}

		// 3. Update overlay badges and popup
		this._updateOverlays();
	},
	_updateOverlays: function () {
		if (!this._$overlay) {
			return;
		}
		this._$overlay.empty();

		if (!this._completedMeasures || this._completedMeasures.length === 0) {
			return;
		}

		var that = this;
		for (var i = 0; i < this._completedMeasures.length; i++) {
			var m = this._completedMeasures[i];
			var pixel = this._map.convertRealToPixel(m.centerPos);
			m.centerPixel = pixel;

			// Badge element
			var badgeBg = (m.id === this._selectedMeasureId) ? "#e65100" : "#ff7f00";
			var badgeTop = (m.type === "point" ? (pixel.y - 12) : pixel.y) + "px";
			var badgeTransform = (m.type === "point") ? "translate(-50%,-100%)" : "translate(-50%,-50%)";
			var $badge = $('<div class="mb-measure-badge" />')
				.css({
					"position": "absolute",
					"left": pixel.x + "px",
					"top": badgeTop,
					"transform": badgeTransform,
					"background-color": badgeBg,
					"color": "#fff",
					"font-family": "Arial,Helvetica,sans-serif",
					"font-size": "12px",
					"font-weight": "bold",
					"padding": "3px 8px",
					"border-radius": "3px",
					"border": "1px solid #cc6600",
					"box-shadow": "0 2px 5px rgba(0,0,0,0.35)",
					"cursor": "pointer",
					"pointer-events": "auto",
					"white-space": "nowrap",
					"z-index": 1002,
					"user-select": "none"
				})
				.text(m.badgeText);

			(function (measureItem) {
				// Prevent mousedown/mouseup from bubbling to map container so pan/featureInfo is not triggered
				$badge.bind("mousedown mouseup click", function (e) {
					e.stopPropagation();
					if (e.type === "click") {
						that.selectMeasure(measureItem.id);
					}
				});
			})(m);

			this._$overlay.append($badge);

			// Popup card (if selected)
			if (m.id === this._selectedMeasureId) {
				var showBelow = (pixel.y < 160);
				var popupTop = showBelow ? (pixel.y + 14) + "px" : (pixel.y - (m.type === "point" ? 36 : 14)) + "px";
				var popupTransform = showBelow ? "translate(-50%,0)" : "translate(-50%,-100%)";

				var $popup = $('<div class="mb-measure-popup" />')
					.css({
						"position": "absolute",
						"left": pixel.x + "px",
						"top": popupTop,
						"transform": popupTransform,
						"background": "#fff",
						"color": "#333",
						"font-family": "Arial,Helvetica,sans-serif",
						"border-radius": "8px",
						"box-shadow": "0 4px 18px rgba(0,0,0,0.22)",
						"border": "1px solid #dcdcdc",
						"padding": "12px 14px",
						"min-width": "300px",
						"max-width": "380px",
						"z-index": 1005,
						"pointer-events": "auto",
						"cursor": "default"
					});

				// Stop all mouse and click events from bubbling up to #mapframe1 to prevent map pan or FeatureInfo
				$popup.bind("mousedown mouseup click dblclick mousemove", function (e) {
					e.stopPropagation();
					that._hideTooltip();
				});

				var $arrow = $('<div />').css({
					"position": "absolute",
					"left": "50%",
					"margin-left": "-8px",
					"width": 0,
					"height": 0,
					"border-left": "8px solid transparent",
					"border-right": "8px solid transparent",
					"pointer-events": "none",
					"top": showBelow ? "-8px" : "auto",
					"bottom": showBelow ? "auto" : "-8px",
					"border-bottom": showBelow ? "8px solid #fff" : "none",
					"border-top": showBelow ? "none" : "8px solid #fff"
				});

				// Header
				var $header = $('<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; padding-bottom:6px; border-bottom:1px solid #eee;" />');
				var $title = $('<span style="font-weight:bold; font-size:13px; color:#222;" />').text(m.title);
				var $close = $('<span style="cursor:pointer; font-size:18px; color:#888; line-height:1; padding:0 2px;" title="Schließen">&times;</span>')
					.hover(function () { $(this).css("color", "#000"); }, function () { $(this).css("color", "#888"); })
					.bind("click", function (e) {
						e.preventDefault();
						e.stopPropagation();
						that.hideMeasurePopup();
					});
				$header.append($title).append($close);

				// Body with measurement value and German copy button
				var $body = $('<div style="display:flex; justify-content:space-between; align-items:flex-start; font-size:12px; line-height:1.6; color:#444; margin-bottom:8px;" />');
				var $valBox = $('<div style="flex:1; min-width:0;" />');
				if (m.type === "polygon") {
					$valBox.append($('<div />').text(m.areaFormatted)).append($('<div />').text(m.perimeterFormatted));
				} else {
					$valBox.append($('<div />').text(m.distanceFormatted));
				}

				// Build German copy text with EPSG
				var srs = (this._map && typeof this._map.getSrs === "function") ? this._map.getSrs() : (this._srs || "EPSG:25832");
				var copyText = "";
				if (m.type === "point") {
					var pt = m.points[0].pos;
					var isGeo = (srs === "EPSG:4326");
					// Format raw coordinate numbers for direct GIS use (no thousand-dots, no labels/units)
					var x = isGeo ? Number(pt.x).toFixed(6) : (Math.round(pt.x * 100) / 100);
					var y = isGeo ? Number(pt.y).toFixed(6) : (Math.round(pt.y * 100) / 100);
					copyText = x + ", " + y + " (" + srs + ")";
				} else if (m.type === "line") {
					copyText = m.distanceFormatted + " (" + srs + ")";
				} else if (m.type === "polygon") {
					copyText = m.areaFormatted + ", " + m.perimeterFormatted + " (" + srs + ")";
				}

				var copySvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1a5fb4" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
				var checkSvg = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#2e7d32" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';

				var $copyBtn = $('<a href="#" title="Messwert in Zwischenablage kopieren" style="flex-shrink:0; margin-left:8px; padding:3px 5px; border-radius:4px; border:1px solid #dcdcdc; background:#f9f9f9; display:inline-flex; align-items:center; cursor:pointer;" />')
					.html(copySvg)
					.hover(
						function () { $(this).css("background", "#eef3fc"); },
						function () { $(this).css("background", "#f9f9f9"); }
					)
					.bind("click", function (e) {
						e.preventDefault();
						e.stopPropagation();
						if (navigator.clipboard && navigator.clipboard.writeText) {
							navigator.clipboard.writeText(copyText);
						} else {
							var $ta = $('<textarea style="position:absolute;left:-9999px;top:0;" />').val(copyText).appendTo("body");
							$ta.select();
							document.execCommand("copy");
							$ta.remove();
						}
						var $btn = $(this);
						$btn.html(checkSvg);
						setTimeout(function () {
							$btn.html(copySvg);
						}, 1500);
					});

				$body.append($valBox).append($copyBtn);

				// Footer with Center, Export (GeoJSON, KML), and Delete (consistent Arial font, 11px size, normal weight)
				var $footer = $('<div style="display:flex; justify-content:space-between; align-items:center; padding-top:8px; border-top:1px solid #eee; font-family:Arial,Helvetica,sans-serif; font-size:11px;" />');
				var $leftActions = $('<div style="display:inline-flex; align-items:center; gap:5px; flex-shrink:0;" />');
				var $centerBtn = $('<a href="#" style="color:#2a62bc; text-decoration:none; cursor:pointer; font-family:Arial,Helvetica,sans-serif; font-size:11px; font-weight:normal;">&#9678; Zentrieren</a>')
					.hover(function () { $(this).css("text-decoration", "underline"); }, function () { $(this).css("text-decoration", "none"); });
				(function (measureItem) {
					$centerBtn.bind("click", function (e) {
						e.preventDefault();
						e.stopPropagation();
						that.centerOnMeasure(measureItem.id);
					});
				})(m);

				var $expGeoJson = $('<a href="#" title="Als GeoJSON exportieren (SRID ist vordefiniert: EPSG:4326)" style="color:#2a62bc; text-decoration:none; cursor:pointer; font-family:Arial,Helvetica,sans-serif; font-size:11px; font-weight:normal;">GeoJSON</a>')
					.hover(function () { $(this).css("text-decoration", "underline"); }, function () { $(this).css("text-decoration", "none"); });
				(function (measureItem) {
					$expGeoJson.bind("click", function (e) {
						e.preventDefault();
						e.stopPropagation();
						that._exportMeasureFile(measureItem, "geojson");
					});
				})(m);

				var $expKml = $('<a href="#" title="Als KML exportieren (SRID ist vordefiniert: EPSG:4326)" style="color:#2a62bc; text-decoration:none; cursor:pointer; font-family:Arial,Helvetica,sans-serif; font-size:11px; font-weight:normal;">KML</a>')
					.hover(function () { $(this).css("text-decoration", "underline"); }, function () { $(this).css("text-decoration", "none"); });
				(function (measureItem) {
					$expKml.bind("click", function (e) {
						e.preventDefault();
						e.stopPropagation();
						that._exportMeasureFile(measureItem, "kml");
					});
				})(m);

				var $expWkt = $('<a href="#" title="Als WKT exportieren (Well-Known Text im aktuellen Koordinatensystem)" style="color:#2a62bc; text-decoration:none; cursor:pointer; font-family:Arial,Helvetica,sans-serif; font-size:11px; font-weight:normal;">WKT</a>')
					.hover(function () { $(this).css("text-decoration", "underline"); }, function () { $(this).css("text-decoration", "none"); });
				(function (measureItem) {
					$expWkt.bind("click", function (e) {
						e.preventDefault();
						e.stopPropagation();
						that._exportMeasureFile(measureItem, "wkt");
					});
				})(m);

				$leftActions.append($centerBtn)
					.append($('<span style="color:#ddd; margin:0 2px;">|</span>'))
					.append($('<span style="color:#777; font-family:Arial,Helvetica,sans-serif; font-size:11px;" title="Export in Standardformaten">Export:</span>'))
					.append($expGeoJson)
					.append($('<span style="color:#bbb; margin:0 1px;">,</span>'))
					.append($expKml)
					.append($('<span style="color:#bbb; margin:0 1px;">,</span>'))
					.append($expWkt);

				var $delBtn = $('<a href="#" style="color:#d93025; text-decoration:none; cursor:pointer; font-family:Arial,Helvetica,sans-serif; font-size:11px; font-weight:normal; margin-left:14px; flex-shrink:0; white-space:nowrap;">&#128465; Löschen</a>')
					.hover(function () { $(this).css("text-decoration", "underline"); }, function () { $(this).css("text-decoration", "none"); });
				(function (measureItem) {
					$delBtn.bind("click", function (e) {
						e.preventDefault();
						e.stopPropagation();
						that.deleteMeasure(measureItem.id);
					});
				})(m);

				$footer.append($leftActions).append($delBtn);
				$popup.append($header).append($body).append($footer).append($arrow);
				this._$overlay.append($popup);
			}
		}
	},
	_escapeHtml: function (str) {
		return String(str || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
	},
	_getDragHandle: function () {
		if (!this._$dragHandle) {
			var that = this;
			this._$dragHandle = $('<div class="mb-measure-drag-handle" />').css({
				"position": "absolute",
				"display": "none",
				"width": "14px",
				"height": "14px",
				"border-radius": "50%",
				"background": "#ffffff",
				"border": "2.5px solid #e65100",
				"box-shadow": "0 2px 6px rgba(0,0,0,0.45)",
				"cursor": "move",
				"pointer-events": "auto",
				"z-index": 1005
			}).appendTo(this.element);

			this._$dragHandle.bind("mousedown", function (e) {
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
				that._onHandleMouseDown(e);
				return false;
			});
		}
		return this._$dragHandle;
	},
	_findSnapTarget: function (mousePos) {
		if (!this._completedMeasures || this._completedMeasures.length === 0) return null;
		// 1. Check vertices first (14px tolerance)
		for (var mIdx = this._completedMeasures.length - 1; mIdx >= 0; mIdx--) {
			var m = this._completedMeasures[mIdx];
			if (!m.points) continue;
			for (var k = 0; k < m.points.length; k++) {
				var pt = m.points[k];
				if (!pt.mousePos) pt.mousePos = this._map.convertRealToPixel(pt.pos);
				var dx = mousePos.x - pt.mousePos.x, dy = mousePos.y - pt.mousePos.y;
				if (dx * dx + dy * dy <= 196) {
					return { type: "vertex", measure: m, index: k, mousePos: pt.mousePos };
				}
			}
		}
		// 2. Check edges (10px tolerance)
		for (var mIdx = this._completedMeasures.length - 1; mIdx >= 0; mIdx--) {
			var m = this._completedMeasures[mIdx];
			if (m.type === "point" || !m.points || m.points.length < 2) continue;
			var count = (m.type === "polygon") ? m.points.length : (m.points.length - 1);
			for (var i = 0; i < count; i++) {
				var p1 = m.points[i].mousePos || this._map.convertRealToPixel(m.points[i].pos);
				var p2 = m.points[(i + 1) % m.points.length].mousePos || this._map.convertRealToPixel(m.points[(i + 1) % m.points.length].pos);
				var segDx = p2.x - p1.x, segDy = p2.y - p1.y, l2 = segDx * segDx + segDy * segDy;
				if (l2 === 0) continue;
				var t = ((mousePos.x - p1.x) * segDx + (mousePos.y - p1.y) * segDy) / l2;
				if (t >= 0 && t <= 1) {
					var qx = p1.x + t * segDx, qy = p1.y + t * segDy;
					var rx = mousePos.x - qx, ry = mousePos.y - qy;
				if (rx * rx + ry * ry <= 100) {
					return {
						type: "edge",
						measure: m,
						segmentIndex: i,
						mousePos: { x: qx, y: qy },
						realPos: this._map.convertPixelToReal({ x: qx, y: qy })
					};
				}
				}
			}
		}
		return null;
	},
	_recalculateMeasure: function (m) {
		if (!m || !m.points || m.points.length === 0) return;
		var srs = (this._map && typeof this._map.getSrs === "function") ? this._map.getSrs() : (this._srs || "EPSG:25832");
		var isGeo = (srs === "EPSG:4326");

		if (m.type === "point") {
			var pt = m.points[0].pos;
			var cX = isGeo ? Number(pt.x).toFixed(5) : Math.round(pt.x).toLocaleString("de-DE");
			var cY = isGeo ? Number(pt.y).toFixed(5) : Math.round(pt.y).toLocaleString("de-DE");
			m.centerPos = { x: pt.x, y: pt.y };
			m.distanceFormatted = (isGeo ? ("Länge: " + cX + "°, Breite: " + cY + "°") : ("Rechtswert: " + cX + " m, Hochwert: " + cY + " m")) + " (" + srs + ")";
			m.badgeText = cX + ", " + cY;
		} else if (m.type === "line") {
			var distVal = 0;
			for (var k = 0; k < m.points.length - 1; k++) {
				distVal += this._calculateDistance(m.points[k].pos, m.points[k + 1].pos);
			}
			m.totalDistance = distVal;
			m.centerPos = this._calculateCentroid(m.points);
			m.distanceFormatted = this._formatMeasure(distVal, false, false) + " Gesamtlänge";
			m.badgeText = this._formatMeasure(distVal, false, true);
		} else if (m.type === "polygon") {
			var saved = this._measurePoints;
			this._measurePoints = m.points;
			var areaVal = this._calculateArea(m.points[0]);
			this._measurePoints = saved;

			var perimVal = 0;
			for (var k = 0; k < m.points.length; k++) {
				perimVal += this._calculateDistance(m.points[k].pos, m.points[(k + 1) % m.points.length].pos);
			}
			m.area = areaVal;
			m.perimeter = perimVal;
			m.totalDistance = perimVal;
			m.centerPos = this._calculateCentroid(m.points);
			m.areaFormatted = this._formatMeasure(areaVal, true, false);
			m.perimeterFormatted = this._formatMeasure(perimVal, false, false) + " Umfang";
			m.badgeText = this._formatMeasure(areaVal, true, true);
		}
	},
	_onHandleMouseDown: function (e) {
		if (!this._activeSnap || this._activeSnap.type === "body") return;
		var that = this;
		var snap = this._activeSnap;
		var m = snap.measure;
		var vIndex;

		if (typeof mb_panActive !== "undefined") mb_panActive = false;
		var panWidget = $(this.element).data("pan") || $(this.element).data("mb_pan");
		if (panWidget) panWidget.active = false;

		if (snap.type === "vertex") {
			vIndex = snap.index;
		} else if (snap.type === "edge") {
			var newPt = { pos: snap.realPos, mousePos: snap.mousePos };
			m.points.splice(snap.segmentIndex + 1, 0, newPt);
			vIndex = snap.segmentIndex + 1;
			this._recalculateMeasure(m);
			this._draw();
		} else {
			return;
		}

		this._isDraggingVertex = true;

		$(document)
			.unbind("mousemove.mb_drag")
			.unbind("mouseup.mb_drag")
			.bind("mousemove.mb_drag", function (moveEvt) {
				moveEvt.preventDefault();
				moveEvt.stopPropagation();
				if (typeof mb_panActive !== "undefined") mb_panActive = false;
				if (panWidget) panWidget.active = false;

				var curMousePos = that._map.getMousePosition(moveEvt);
				m.points[vIndex].mousePos = curMousePos;
				m.points[vIndex].pos = that._map.convertPixelToReal(curMousePos);
				that._recalculateMeasure(m);
				that._draw();

				that._getDragHandle().css({
					left: (curMousePos.x - 7) + "px",
					top: (curMousePos.y - 7) + "px"
				}).show();

				var liveVal = (m.type === "polygon") ?
					("Fläche: " + m.areaFormatted + " (Umfang: " + m.perimeterFormatted + ")") :
					(m.type === "line" ? m.distanceFormatted : m.badgeText);
				that._updateTooltip(curMousePos, liveVal, "Punkt wird verschoben...");
			})
			.bind("mouseup.mb_drag", function (upEvt) {
				upEvt.preventDefault();
				upEvt.stopPropagation();
				that._isDraggingVertex = false;
				$(document).unbind("mousemove.mb_drag").unbind("mouseup.mb_drag");

				that._justFinishedDrag = true;
				setTimeout(function () { that._justFinishedDrag = false; }, 150);

				that._recalculateMeasure(m);
				that._draw();
				that._updateTooltip(m.points[vIndex].mousePos, "Punkt verschoben", "Klicken und ziehen zum Verschieben");
			});
	},
	_updateTooltip: function (mousePos, line1, line2) {
		if (!this._$tooltip) {
			this._$tooltip = $("<div class='mb-measure-tooltip' />").css({
				"position": "absolute",
				"display": "none",
				"pointer-events": "none",
				"background": "rgba(20, 24, 35, 0.88)",
				"color": "#ffffff",
				"font-family": "Arial, Helvetica, sans-serif",
				"padding": "5px 9px",
				"border-radius": "4px",
				"box-shadow": "0 2px 7px rgba(0,0,0,0.38)",
				"z-index": 1006,
				"min-width": "140px",
				"white-space": "nowrap"
			}).appendTo(this.element);
		}
		if (!line1) {
			this._$tooltip.hide();
			return;
		}

		var escLine1 = this._escapeHtml(line1);
		var html = "";
		if (line2) {
			var escLine2 = this._escapeHtml(line2);
			html = '<div style="font-weight:bold; font-size:11px; line-height:14px; margin-bottom:2px; color:#ffffff;">' +
				escLine1 +
				'</div><div style="font-size:10px; line-height:13px; color:#c8d4e5;">' +
				escLine2 +
				'</div>';
		} else {
			html = '<div style="font-size:11px; line-height:14px; color:#ffffff;">' + escLine1 + '</div>';
		}

		this._$tooltip.html(html).show();
		var tipW = this._$tooltip.outerWidth() || 180;
		var tipH = this._$tooltip.outerHeight() || 32;
		var mapW = (this._map && typeof this._map.getWidth === "function") ? this._map.getWidth() : $(this.element).width();
		var mapH = (this._map && typeof this._map.getHeight === "function") ? this._map.getHeight() : $(this.element).height();
		var tx = mousePos.x + 15;
		var ty = mousePos.y + 15;
		if (mapW && (tx + tipW + 10 > mapW)) {
			tx = Math.max(5, mousePos.x - tipW - 10);
		}
		if (mapH && (ty + tipH + 10 > mapH)) {
			ty = Math.max(5, mousePos.y - tipH - 10);
		}
		this._$tooltip.css({ left: tx + "px", top: ty + "px" });
	},
	_hideTooltip: function () {
		if (this._$tooltip) this._$tooltip.hide();
		if (this._$dragHandle && !this._isDraggingVertex) this._$dragHandle.hide();
	},
	_onKeyDown: function (e) {
		if ($(e.target).is("input, textarea, select") || (e.target && e.target.isContentEditable)) return;
		if (e.ctrlKey || e.metaKey || e.altKey) return;
		var keyCode = e.which || e.keyCode;

		// Esc (27): Cancel active drawing or close popup
		if (keyCode === 27) {
			if (this._measurePoints && this._measurePoints.length > 0) {
				e.preventDefault(); e.stopPropagation();
				this._measurePoints = [];
				this._totalDistance = 0;
				this._currentDistance = 0;
				this._polygonIsInvalid = false;
				this._currentPolygonIsInvalid = false;
				this._draw();
				if (this._lastMousePos) {
					this._updateTooltip(this._lastMousePos, "Messung beginnen", "Klick: Punkt setzen | Doppelklick: Punktmessung");
				} else {
					this._hideTooltip();
				}
				return false;
			} else if (this._selectedMeasureId) {
				e.preventDefault(); e.stopPropagation();
				this.hideMeasurePopup();
				return false;
			}
		}

		// Backspace (8) or Delete / Entf (46)
		if (keyCode === 8 || keyCode === 46) {
			if (this._measurePoints && this._measurePoints.length > 0) {
				e.preventDefault(); e.stopPropagation();
				this._measurePoints.pop();
				this._polygonIsInvalid = false;
				this._currentPolygonIsInvalid = false;
				var len = this._measurePoints.length;
				if (len === 0) {
					this._totalDistance = 0;
					this._currentDistance = 0;
					this._draw();
				if (this._lastMousePos) {
					this._updateTooltip(this._lastMousePos, "Messung beginnen", "Klick: Punkt setzen | Doppelklick: Punktmessung");
				} else {
					this._hideTooltip();
				}
			} else {
				this._totalDistance = this._measurePoints[len - 1].totalDistance || 0;
				if (this._lastMouseMoveEvent) {
					this._measure(this._lastMouseMoveEvent);
				} else {
					this._draw();
				}
			}
			return false;
		} else {
			var delId = (this._activeSnap ? this._activeSnap.measure.id : null) ||
				(this._hoveredMeasure ? this._hoveredMeasure.id : null) ||
				this._selectedMeasureId;
			if (delId) {
				e.preventDefault(); e.stopPropagation();
				this.deleteMeasure(delId);
				this._activeSnap = null;
				this._hoveredMeasure = null;
				if (this._$dragHandle) this._$dragHandle.hide();
				if (this._lastMousePos) {
					this._updateTooltip(this._lastMousePos, "Messung gelöscht", "Klick: Messung beginnen | Doppelklick: Punktmessung");
				} else {
					this._hideTooltip();
				}
				return false;
			}
		}
	}
	},
	_measure: function (e) {
		var mousePos = this._map.getMousePosition(e);
		this._lastMouseMoveEvent = e;
		this._lastMousePos = mousePos;

		if (this._isDraggingVertex) {
			return;
		}

		// When not in the middle of drawing a new measurement
		if (this._measurePoints.length === 0) {
			var snap = this._findSnapTarget(mousePos);
			var prevHoverId = this._hoveredMeasure ? this._hoveredMeasure.id : null;
			var newHoverId = snap ? snap.measure.id : null;
			this._activeSnap = (snap && snap.type !== "body") ? snap : null;
			this._hoveredMeasure = snap ? snap.measure : null;

			// Highlight changed: redraw canvas to highlight active line/polygon
			if (prevHoverId !== newHoverId) {
				this._draw();
			}

			if (snap) {
				var m = snap.measure;
				var valStr = (m.type === "polygon") ? m.areaFormatted : m.distanceFormatted;
				if (snap.type === "vertex") {
					this._getDragHandle().css({
						left: (snap.mousePos.x - 7) + "px",
						top: (snap.mousePos.y - 7) + "px",
						background: "#ffffff",
						borderColor: "#e65100",
						cursor: "move"
					}).show();
					this._updateTooltip(snap.mousePos, "Punkt verschieben (" + m.title + ")", "Klicken und ziehen zum Verschieben | [Entf] Messung löschen");
					return;
				} else if (snap.type === "edge") {
					this._getDragHandle().css({
						left: (snap.mousePos.x - 6) + "px",
						top: (snap.mousePos.y - 6) + "px",
						background: "#ff9800",
						borderColor: "#ffffff",
						cursor: "copy"
					}).show();
					this._updateTooltip(snap.mousePos, "Neuer Stützpunkt (" + m.title + ")", "Klicken und ziehen zum Hinzufügen | [Entf] Messung löschen");
					return;
				}
			}

			// Free map space (or inside polygon interior): ready to start new measurement
			if (this._$dragHandle) this._$dragHandle.hide();
			$(this.element).css("cursor", "crosshair");
			this._updateTooltip(mousePos, "Messung beginnen", "Klick: Punkt setzen | Doppelklick: Punktmessung");
			return;
		}

		var mousePos = this._map.getMousePosition(e);
		var firstPointSnapped = this._isFirstPointSnapped(mousePos)
			&& !this._polygonIsInvalid;
		var lastPointSnapped = this._isLastPointSnapped(mousePos);

		var measureData = {
			pos: {
				mousePos: mousePos,
				pos: firstPointSnapped ?
					this._measurePoints[0].pos : lastPointSnapped ?
						this._measurePoints[this._measurePoints.length - 1].pos :
						this._map.convertPixelToReal(mousePos)
			}
		};

		//
		// calculate distance
		//
		var len = this._measurePoints.length;
		var previousPoint = len > 0 ?
			this._measurePoints[len - 1].pos : null;

		this._currentDistance = this._calculateDistance(
			previousPoint,
			measureData.pos.pos
		);

		//
		// calculate total distance and perimeter
		//
		if (len > 0) {
			measureData.currentDistance = this._currentDistance;
			this._totalDistance = this._currentDistance;

			if (!firstPointSnapped) {
				measureData.totalDistance = this._totalDistance ;
			}

			if (len > 1) {
				this._totalDistance = this._measurePoints[len - 1].totalDistance + this._currentDistance;
				if (!firstPointSnapped) {
					measureData.totalDistance = this._totalDistance;
				}
				if (!lastPointSnapped) {
					measureData.perimeter = this._totalDistance + this._calculateDistance(
						this._measurePoints[0].pos,
						measureData.pos.pos
					);
				}

				this._currentAngle = this._calculateAngle(
					this._measurePoints[len-2].pos,
					previousPoint,
					measureData.pos.pos
				);
				measureData.currentAngle = this._currentAngle;
			}
		}

		//
		// calculate area
		//
		if (this._isPolygon(this._measurePoints, measureData.pos) && !this._polygonIsInvalid) {
			this._currentArea = this._calculateArea(measureData.pos);
			if (!lastPointSnapped) {
				measureData.area = this._currentArea;
			}
		}

		// Update live segment preview tooltip with flicker-free two-line layout
		if (len === 1) {
			if (lastPointSnapped) {
				this._updateTooltip(mousePos, "Punktmessung", "Doppelklick zum Beenden");
			} else {
				var dist1 = this._formatMeasure(this._currentDistance, false, true);
				this._updateTooltip(mousePos, dist1 || "Streckenmessung", "Doppelklick zum Beenden | [Esc] Abbrechen");
			}
		} else if (len >= 2) {
			if (firstPointSnapped) {
				var areaN = this._formatMeasure(this._currentArea, true, true);
				var perimN = this._formatMeasure(measureData.perimeter || this._totalDistance, false, true);
				this._updateTooltip(mousePos, "Fläche: " + areaN + (perimN ? " (Umfang: " + perimN + ")" : ""), "Klicken zum Beenden (Fläche schließen) | [Entf] letzter Punkt");
			} else {
				var distN = this._formatMeasure(this._totalDistance, false, true);
				var areaN = (measureData.area && !this._polygonIsInvalid) ? this._formatMeasure(measureData.area, true, true) : null;
				this._updateTooltip(mousePos, distN + (areaN ? " | " + areaN : ""), "Doppelklick zum Beenden | [Entf] letzter Punkt | [Esc] Abbrechen");
			}
		}

		this._trigger("update", null, measureData);

		this._draw(measureData.pos, {
			highlightFirst: firstPointSnapped,
			highlightLast: lastPointSnapped,
			drawPoints: true
		});
	},
	_reinitialize: function (e) {
		this.element.unbind("click", $.proxy(this, "_reinitialize"));
		this._trigger("reinitialize", e);
		return false;
	},
	_addLastPoint: function (e) {
		this._trigger("lastpointadded", e);
	},
	_addPoint: function (e) {
		// Only primary left-click adds points (ignores middle/right clicks)
		if (e.which && e.which !== 1) {
			return;
		}
		// Ignore click immediately after vertex dragging
		if (this._justFinishedDrag) {
			this._justFinishedDrag = false;
			return false;
		}
		// Ensure FeatureInfo does not trigger on map clicks during active measurement
		if (typeof Mapbender !== "undefined" && typeof Mapbender.disableFeatureInfo === "function") {
			Mapbender.disableFeatureInfo();
		}
		var mousePos = this._map.getMousePosition(e);

		// When not drawing, clicking on an existing measure selects it; clicking empty space deselects
		if (this._measurePoints.length === 0) {
			if (this._isNewMeasureActive) {
				this._isNewMeasureActive = false;
				this.hideMeasurePopup();
				this._selectedMeasureId = null;
				this._hoveredMeasure = null;
				this._activeSnap = null;
				this._draw();
			} else {
				if (this._activeSnap) {
					return false;
				}
				if (this._hoveredMeasure) {
					this.selectMeasure(this._hoveredMeasure.id);
					return false;
				}
				this.hideMeasurePopup();
				this._selectedMeasureId = null;
				this._draw();
			}
		}

		var len = this._measurePoints.length;

		var data = {
			pos: {
				mousePos: mousePos,
				pos: this._map.convertPixelToReal(mousePos)
			}
		};

		if (this._totalDistance) {
			data.pos.totalDistance = this._totalDistance;
		}

		this._trigger("pointadded", e, data);

		var firstPointSnapped = this._isFirstPointSnapped(mousePos);
		var lastPointSnapped = this._isLastPointSnapped(mousePos);

		this._isPolygon(this._measurePoints, data.pos);
		if (this._currentPolygonIsInvalid) {
			this._polygonIsInvalid = true;
		}
		this._currentPolygonIsInvalid = false;

		if (lastPointSnapped || firstPointSnapped) {
			var isPolygon = firstPointSnapped;
			var pts = [];
			for (var i = 0; i < this._measurePoints.length; i++) {
				pts.push({
					pos: { x: this._measurePoints[i].pos.x, y: this._measurePoints[i].pos.y },
					mousePos: { x: this._measurePoints[i].mousePos.x, y: this._measurePoints[i].mousePos.y }
				});
			}

			var measureRecord;
			if (pts.length === 1) {
				var srs = (this._map && typeof this._map.getSrs === "function") ? this._map.getSrs() : (this._srs || "EPSG:25832");
				var pt = pts[0].pos, isGeo = (srs === "EPSG:4326");
				var cX = isGeo ? Number(pt.x).toFixed(5) : Math.round(pt.x).toLocaleString("de-DE");
				var cY = isGeo ? Number(pt.y).toFixed(5) : Math.round(pt.y).toLocaleString("de-DE");
				measureRecord = {
					id: "measure_" + (++this._measureCounter), type: "point", points: pts, totalDistance: 0,
					centerPos: pt, title: "Punktmessung",
					distanceFormatted: (isGeo ? ("Länge: " + cX + "°, Breite: " + cY + "°") : ("Rechtswert: " + cX + " m, Hochwert: " + cY + " m")) + " (" + srs + ")",
					badgeText: cX + ", " + cY
				};
			} else if (isPolygon) {
				var areaVal = this._currentArea;
				var perimVal = 0;
				for (var k = 0; k < pts.length; k++) {
					perimVal += this._calculateDistance(pts[k].pos, pts[(k + 1) % pts.length].pos);
				}
				var center = this._calculateCentroid(pts);
				measureRecord = {
					id: "measure_" + (++this._measureCounter),
					type: "polygon",
					points: pts,
					area: areaVal,
					perimeter: perimVal,
					totalDistance: perimVal,
					centerPos: center,
					title: "Flächenmessung",
					areaFormatted: this._formatMeasure(areaVal, true, false),
					perimeterFormatted: this._formatMeasure(perimVal, false, false) + " Umfang",
					distanceFormatted: "",
					badgeText: this._formatMeasure(areaVal, true, true)
				};
			} else {
				var distVal = 0;
				for (var k = 0; k < pts.length - 1; k++) {
					distVal += this._calculateDistance(pts[k].pos, pts[k + 1].pos);
				}
				var center = this._calculateCentroid(pts);
				measureRecord = {
					id: "measure_" + (++this._measureCounter),
					type: "line",
					points: pts,
					area: null,
					perimeter: null,
					totalDistance: distVal,
					centerPos: center,
					title: "Streckenmessung",
					areaFormatted: "",
					perimeterFormatted: "",
					distanceFormatted: this._formatMeasure(distVal, false, false) + " Gesamtlänge",
					badgeText: this._formatMeasure(distVal, false, true)
				};
			}

			this._completedMeasures.push(measureRecord);
			this._selectedMeasureId = measureRecord.id;

			// Reset in-progress state
			this._measurePoints = [];
			this._totalDistance = 0;
			this._currentDistance = 0;
			this._polygonIsInvalid = false;
			this._currentPolygonIsInvalid = false;

			// Redraw all completed measurements
			this._draw();

			// Trigger lastpointadded event with completed measure details
			this._trigger("lastpointadded", e, {
				measure: measureRecord,
				allMeasures: this._completedMeasures
			});
		}
		else {
			this._measurePoints.push(data.pos);
			this._currentDistance = 0;

			lastPointSnapped = this._isLastPointSnapped(mousePos);
			this._draw(data.pos, {
				highlightFirst: firstPointSnapped,
				highlightLast: lastPointSnapped,
				drawPoints: true
			});
		}
		return true;
	},
	_redraw: function () {
		if (!$(this.element).data("mb_measure")) {
			return;
		}
		// Keep FeatureInfo suppressed during active measurement
		if (typeof Mapbender !== "undefined" && typeof Mapbender.disableFeatureInfo === "function") {
			Mapbender.disableFeatureInfo();
		}
		var hasCompleted = (this._completedMeasures && this._completedMeasures.length > 0);
		var hasInProgress = (this._measurePoints && this._measurePoints.length > 0);
		if (!hasCompleted && !hasInProgress) {
			if (this._srs && this._map && this._map.getSrs() != this._srs) {
				this._srs = this._map.getSrs();
			}
			return;
		}

		if (this._$canvas) {
			this._$canvas.css({ left: 0, top: 0 });
		}
		if (this._$overlay) {
			this._$overlay.css({ left: 0, top: 0 });
		}

		// Coordinate reference system changed (same logic as in w_hohe.js):
		if (this._srs && this._map.getSrs() != this._srs && typeof Proj4js !== "undefined") {
			var source = new Proj4js.Proj(this._srs);
			var dest = new Proj4js.Proj(this._map.getSrs());
			if (source.readyToUse && dest.readyToUse) {
				for (var mIdx = 0; mIdx < this._completedMeasures.length; mIdx++) {
					var m = this._completedMeasures[mIdx];
					for (var pIdx = 0; pIdx < m.points.length; pIdx++) {
						var pt = m.points[pIdx];
						var p2 = Proj4js.transform(source, dest, new Proj4js.Point(pt.pos.x, pt.pos.y));
						pt.pos = { x: p2.x, y: p2.y };
					}
					m.centerPos = (m.type === "point") ? m.points[0].pos : this._calculateCentroid(m.points);
					if (m.type === "polygon") {
						var savedPts = this._measurePoints;
						this._measurePoints = m.points.slice(0, m.points.length - 1);
						m.area = this._calculateArea(m.points[m.points.length - 1]);
						this._measurePoints = savedPts;

						var pSum = 0;
						for (var k = 0; k < m.points.length; k++) {
							pSum += this._calculateDistance(m.points[k].pos, m.points[(k + 1) % m.points.length].pos);
						}
						m.perimeter = pSum;
						m.totalDistance = pSum;
						m.areaFormatted = this._formatMeasure(m.area, true, false);
						m.perimeterFormatted = this._formatMeasure(m.perimeter, false, false) + " Umfang";
						m.badgeText = this._formatMeasure(m.area, true, true);
					} else if (m.type === "line") {
						var distSum = 0;
						for (var k = 0; k < m.points.length - 1; k++) {
							distSum += this._calculateDistance(m.points[k].pos, m.points[k + 1].pos);
						}
						m.totalDistance = distSum;
						m.distanceFormatted = this._formatMeasure(distSum, false, false) + " Gesamtlänge";
						m.badgeText = this._formatMeasure(distSum, false, true);
					} else if (m.type === "point") {
						var srs = (this._map && typeof this._map.getSrs === "function") ? this._map.getSrs() : (this._srs || "EPSG:25832");
						var pt = m.points[0].pos, isGeo = (srs === "EPSG:4326");
						var cX = isGeo ? Number(pt.x).toFixed(5) : Math.round(pt.x).toLocaleString("de-DE");
						var cY = isGeo ? Number(pt.y).toFixed(5) : Math.round(pt.y).toLocaleString("de-DE");
						m.distanceFormatted = (isGeo ? ("Länge: " + cX + "°, Breite: " + cY + "°") : ("Rechtswert: " + cX + " m, Hochwert: " + cY + " m")) + " (" + srs + ")";
						m.badgeText = cX + ", " + cY;
					}
				}
				for (var i = 0; i < this._measurePoints.length; i++) {
					var mp = this._measurePoints[i];
					var mp2 = Proj4js.transform(source, dest, new Proj4js.Point(mp.pos.x, mp.pos.y));
					mp.pos = { x: mp2.x, y: mp2.y };
				}
			}
			this._srs = this._map.getSrs();
			// Ensure FeatureInfo remains disabled during measurement if mod_changeEPSG re-enabled it
			if (typeof Mapbender !== "undefined" && typeof Mapbender.disableFeatureInfo === "function") {
				Mapbender.disableFeatureInfo();
			}
			this._trigger("srschanged", null, { allMeasures: this._completedMeasures });
		}

		if (hasInProgress) {
			for (var i = 0; i < this._measurePoints.length; i++) {
				var p = this._measurePoints[i];
				p.mousePos = this._map.convertRealToPixel(p.pos);
			}
		}

		this._draw(undefined, {
			highlightFirst: false,
			highlightLast: false,
			drawPoints: false
		});
	},
	_init: function () {
		var that = this;
		this._measureProxy = this._measureProxy || $.proxy(this, "_measure");
		this._addPointProxy = this._addPointProxy || $.proxy(this, "_addPoint");
		this._onKeyDownProxy = this._onKeyDownProxy || $.proxy(this, "_onKeyDown");
		this._hideTooltipProxy = this._hideTooltipProxy || $.proxy(this, "_hideTooltip");

		this.element
			.unbind("mousedown.mb_measure_block")
			.unbind("click", this._addPointProxy)
			.unbind("mousemove", this._measureProxy)
			.unbind("mouseleave", this._hideTooltipProxy)
			.unbind("click", this._addPoint)
			.unbind("mousemove", this._measure)
			.bind("mousedown.mb_measure_block", function (e) {
				if (that._activeSnap && that._activeSnap.type !== "body") {
					e.preventDefault();
					e.stopPropagation();
					e.stopImmediatePropagation();
					that._onHandleMouseDown(e);
					return false;
				}
			})
			.bind("click", this._addPointProxy)
			.bind("mousemove", this._measureProxy)
			.bind("mouseleave", this._hideTooltipProxy)
			.css("cursor", "crosshair");

		$(document)
			.unbind("keydown.mb_measure")
			.bind("keydown.mb_measure", this._onKeyDownProxy);
	},
	_create: function () {
		this._measurePoints = [];
		this._completedMeasures = [];
		this._measureCounter = 0;
		this._selectedMeasureId = null;
		this._lastMousePos = null;
		this._lastMouseMoveEvent = null;
		this._activeSnap = null;
		this._hoveredMeasure = null;
		this._isDraggingVertex = false;
		this._justFinishedDrag = false;

		// ":maps" is a Mapbender selector which
		// checks if an element is a Mapbender map
		this.element = this.element.filter(":maps");

		if (!this.element.jquery || this.element.size() === 0) {
			$.error("This widget must be applied to a Mapbender map.");
		}

		this._map = this.element.mapbender();
		// Store proxy references so unbind/unregister match exact function identity
		this._redrawProxy = $.proxy(this._redraw, this);
		this._measureProxy = $.proxy(this, "_measure");
		this._addPointProxy = $.proxy(this, "_addPoint");
		this._map.events.afterMapRequest.register(this._redrawProxy);
		this._srs = this._map.getSrs();

		this._$canvas = $("<div id='measure_canvas' />").css({
			"z-index": 1000,
			"position": "absolute",
			// Pass mouse events through to #mapframe1 so _canvas.clear() on mousemove cannot detach the mousedown target mid-click
			"pointer-events": "none"
		}).appendTo(this.element);
		this._canvas = Raphael(this._$canvas.get(0), this._map.getWidth(), this._map.getHeight());
		mb_registerPanSubElement($(this._canvas.canvas).parent().get(0));

		this._$overlay = $("<div id='measure_overlay' />").css({
			"z-index": 1001,
			"position": "absolute",
			"top": 0,
			"left": 0,
			"width": "100%",
			"height": "100%",
			"pointer-events": "none",
			"overflow": "visible"
		}).appendTo(this.element);
		mb_registerPanSubElement(this._$overlay.get(0));
	},
	getMeasures: function () {
		return this._completedMeasures;
	},
	getMeasureById: function (id) {
		if (!this._completedMeasures) {
			return null;
		}
		for (var i = 0; i < this._completedMeasures.length; i++) {
			if (this._completedMeasures[i].id === id) {
				return this._completedMeasures[i];
			}
		}
		return null;
	},
	selectMeasure: function (id) {
		this._selectedMeasureId = id;
		this._draw();
	},
	centerOnMeasure: function (id) {
		var m = this.getMeasureById(id);
		if (!m || !m.points || m.points.length === 0) {
			return;
		}

		var minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
		for (var i = 0; i < m.points.length; i++) {
			var p = m.points[i].pos;
			if (p.x < minX) minX = p.x;
			if (p.x > maxX) maxX = p.x;
			if (p.y < minY) minY = p.y;
			if (p.y > maxY) maxY = p.y;
		}

		var width = maxX - minX;
		var height = maxY - minY;

		// Add 25% padding around the bounding box so the entire geometry is clearly framed
		var padX = width * 0.25;
		var padY = height * 0.25;
		var isGeographic = (this._map.getSrs() === "EPSG:4326");
		var minPad = isGeographic ? 0.001 : 25;
		if (padX < minPad) padX = (padY >= minPad) ? padY : minPad;
		if (padY < minPad) padY = (padX >= minPad) ? padX : minPad;

		minX -= padX;
		maxX += padX;
		minY -= padY;
		maxY += padY;

		var newExtent = new Mapbender.Extent(minX, minY, maxX, maxY);
		this._map.calculateExtent(newExtent);
		this._map.setMapRequest();
	},
	deleteMeasure: function (id) {
		var removed = null;
		var updated = [];
		for (var i = 0; i < this._completedMeasures.length; i++) {
			if (this._completedMeasures[i].id === id) {
				removed = this._completedMeasures[i];
			} else {
				updated.push(this._completedMeasures[i]);
			}
		}
		this._completedMeasures = updated;
		if (this._selectedMeasureId === id) {
			this._selectedMeasureId = null;
		}
		this._draw();
		var eventData = {
			deleted: removed,
			allMeasures: this._completedMeasures
		};
		this._trigger("deleted", null, eventData);
		this._trigger("measuredeleted", null, eventData);
		return this._completedMeasures;
	},
	clearAllMeasures: function () {
		this._completedMeasures = [];
		this._selectedMeasureId = null;
		this._measurePoints = [];
		this._totalDistance = 0;
		this._currentDistance = 0;
		this._draw();
		var eventData = {
			allMeasures: []
		};
		this._trigger("allcleared", null, eventData);
		this._trigger("allmeasurescleared", null, eventData);
		return this._completedMeasures;
	},
	startNewMeasure: function () {
		// Ensure FeatureInfo remains disabled when starting a new measurement
		if (typeof Mapbender !== "undefined" && typeof Mapbender.disableFeatureInfo === "function") {
			Mapbender.disableFeatureInfo();
		}
		this._isNewMeasureActive = true;
		this._measurePoints = [];
		this._totalDistance = 0;
		this._currentDistance = 0;
		this._selectedMeasureId = null;
		this._hoveredMeasure = null;
		this._activeSnap = null;
		if (this._$dragHandle) {
			this._$dragHandle.hide();
		}
		this.hideMeasurePopup();
		// Make measurement tool active: re-bind click and mousemove handlers and set crosshair cursor
		this._init();
		this._draw();
		if (this._lastMousePos) {
			this._updateTooltip(this._lastMousePos, "Messung beginnen", "Klick: Punkt setzen | Doppelklick: Punktmessung");
		}
	},
	showMeasurePopup: function (id) {
		this._selectedMeasureId = id;
		this._draw();
	},
	hideMeasurePopup: function () {
		this._selectedMeasureId = null;
		this._draw();
	},
	prepareForPrint: function () {
		this._draw(null, { isPrinting: true });
	},
	redraw: function () {
		this._draw();
	},
	// Export measurement geometry to GeoJSON or KML file
	_exportMeasureFile: function (measure, format) {
		if (!measure || !measure.points || measure.points.length === 0) {
			return;
		}
		var srs = (this._map && typeof this._map.getSrs === "function") ? this._map.getSrs() : (this._srs || "EPSG:25832");

		// Transform coordinates to WGS84 (EPSG:4326) as required by GeoJSON (RFC 7946) and KML standards
		var wgs84 = [];
		var isGeo = (srs === "EPSG:4326");
		var srcProj = null;
		var dstProj = null;

		if (!isGeo) {
			if (typeof Proj4js === "undefined" || !Proj4js.Proj) {
				alert("Koordinatentransformation nicht verfügbar – Export abgebrochen.");
				return;
			}
			srcProj = new Proj4js.Proj(srs);
			dstProj = new Proj4js.Proj("EPSG:4326");
			if (!srcProj || !dstProj || !srcProj.readyToUse || !dstProj.readyToUse) {
				alert("Koordinatentransformation nicht verfügbar – Export abgebrochen.");
				return;
			}
		}

		for (var i = 0; i < measure.points.length; i++) {
			var pt = measure.points[i].pos;
			if (!isGeo) {
				try {
					var res = Proj4js.transform(srcProj, dstProj, new Proj4js.Point(pt.x, pt.y));
					if (!res || isNaN(res.x) || isNaN(res.y)) {
						throw new Error("Ungültige Koordinatentransformation");
					}
					wgs84.push([Number(res.x.toFixed(6)), Number(res.y.toFixed(6))]);
				} catch (err) {
					alert("Fehler bei der Koordinatentransformation – Export abgebrochen.");
					return;
				}
			} else {
				wgs84.push([Number(Number(pt.x).toFixed(6)), Number(Number(pt.y).toFixed(6))]);
			}
		}

		var fileBaseName = (measure.title || "messung").toLowerCase().replace(/ä/g, "ae").replace(/[^a-z0-9_-]/g, "_");
		if (format === "geojson") {
			var geom = null;
			var props = { title: measure.title, srs: "EPSG:4326", map_srs: srs };
			if (measure.type === "point") {
				geom = { type: "Point", coordinates: wgs84[0] };
			} else if (measure.type === "line") {
				geom = { type: "LineString", coordinates: wgs84 };
				props.total_distance_m = Math.round(measure.totalDistance * 10) / 10;
				props.formatted = measure.distanceFormatted;
			} else if (measure.type === "polygon") {
				var ring = wgs84.slice(0);
				if (ring.length > 0 && (ring[0][0] !== ring[ring.length - 1][0] || ring[0][1] !== ring[ring.length - 1][1])) {
					ring.push(ring[0]);
				}
				geom = { type: "Polygon", coordinates: [ring] };
				props.area_sqm = Math.round(measure.area * 10) / 10;
				props.perimeter_m = Math.round(measure.perimeter * 10) / 10;
				props.area_formatted = measure.areaFormatted;
				props.perimeter_formatted = measure.perimeterFormatted;
			}
			var geojson = {
				type: "FeatureCollection",
				name: measure.title,
				features: [{
					type: "Feature",
					properties: props,
					geometry: geom
				}]
			};
			this._downloadBlob(JSON.stringify(geojson, null, 2), fileBaseName + ".geojson", "application/geo+json");
		} else if (format === "kml") {
			var coordsStr = "";
			for (var k = 0; k < wgs84.length; k++) {
				coordsStr += wgs84[k][0] + "," + wgs84[k][1] + ",0 ";
			}
			coordsStr = coordsStr.trim();

			var geomXml = "";
			var desc = "";
			if (measure.type === "point") {
				geomXml = "<Point><coordinates>" + wgs84[0][0] + "," + wgs84[0][1] + ",0</coordinates></Point>";
				desc = "Koordinaten: " + wgs84[0][0] + "°, " + wgs84[0][1] + "° (EPSG:4326)" + (srs !== "EPSG:4326" ? " | Karte: " + srs : "");
			} else if (measure.type === "line") {
				geomXml = "<LineString><tessellate>1</tessellate><coordinates>" + coordsStr + "</coordinates></LineString>";
				desc = measure.distanceFormatted || "";
			} else if (measure.type === "polygon") {
				var closedCoords = coordsStr;
				if (wgs84.length > 0 && (wgs84[0][0] !== wgs84[wgs84.length - 1][0] || wgs84[0][1] !== wgs84[wgs84.length - 1][1])) {
					closedCoords += " " + wgs84[0][0] + "," + wgs84[0][1] + ",0";
				}
				geomXml = "<Polygon><outerBoundaryIs><LinearRing><coordinates>" + closedCoords + "</coordinates></LinearRing></outerBoundaryIs></Polygon>";
				desc = (measure.areaFormatted || "") + ", " + (measure.perimeterFormatted || "");
			}
			function escapeXml(s) {
				return (s || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&apos;");
			}
			var kml = '<?xml version="1.0" encoding="UTF-8"?>\n' +
				'<kml xmlns="http://www.opengis.net/kml/2.2">\n' +
				'  <Document>\n' +
				'    <name>' + escapeXml(measure.title || "Messung") + '</name>\n' +
				'    <Placemark>\n' +
				'      <name>' + escapeXml(measure.title || "Messung") + '</name>\n' +
				'      <description>' + escapeXml(desc) + '</description>\n' +
				'      ' + geomXml + '\n' +
				'    </Placemark>\n' +
				'  </Document>\n' +
				'</kml>';
			this._downloadBlob(kml, fileBaseName + ".kml", "application/vnd.google-earth.kml+xml");
		} else if (format === "wkt") {
			var isGeoMap = (srs === "EPSG:4326");
			var wktCoords = [];
			for (var k = 0; k < measure.points.length; k++) {
				var p = measure.points[k].pos;
				var px = isGeoMap ? Number(p.x).toFixed(6) : (Math.round(p.x * 100) / 100);
				var py = isGeoMap ? Number(p.y).toFixed(6) : (Math.round(p.y * 100) / 100);
				wktCoords.push(px + " " + py);
			}
			var wkt = "";
			if (measure.type === "point") {
				wkt = "POINT (" + wktCoords[0] + ")";
			} else if (measure.type === "line") {
				wkt = "LINESTRING (" + wktCoords.join(", ") + ")";
			} else if (measure.type === "polygon") {
				if (wktCoords.length > 0 && wktCoords[0] !== wktCoords[wktCoords.length - 1]) {
					wktCoords.push(wktCoords[0]);
				}
				wkt = "POLYGON ((" + wktCoords.join(", ") + "))";
			}
			this._downloadBlob(wkt, fileBaseName + ".wkt", "text/plain");
		}
	},
	// Trigger browser file download from string content
	_downloadBlob: function (content, filename, mimeType) {
		var blob = new Blob([content], { type: mimeType + ";charset=utf-8" });
		if (window.navigator && window.navigator.msSaveOrOpenBlob) {
			window.navigator.msSaveOrOpenBlob(blob, filename);
			return;
		}
		var url = URL.createObjectURL(blob);
		var a = document.createElement("a");
		a.href = url;
		a.download = filename;
		document.body.appendChild(a);
		a.click();
		setTimeout(function () {
			document.body.removeChild(a);
			URL.revokeObjectURL(url);
		}, 100);
	},
	// the measured geometry will be available, the events will be deleted
	deactivate: function () {
		this.hideMeasurePopup();
		this._hideTooltip();
		this._isDraggingVertex = false;
		$(document)
			.unbind("keydown.mb_measure")
			.unbind("mousemove.mb_drag")
			.unbind("mouseup.mb_drag");
		if (this._addPointProxy) {
			this.element.unbind("click", this._addPointProxy);
		}
		if (this._measureProxy) {
			this.element.unbind("mousemove", this._measureProxy);
		}
		if (this._hideTooltipProxy) {
			this.element.unbind("mouseleave", this._hideTooltipProxy);
		}
		this.element
			.unbind("mousedown.mb_measure_block")
			.unbind("click", this._addPoint)
			.unbind("mousemove", this._measure)
			.unbind("click", this._reinitialize)
			.css("cursor", "default");
	},
	// delete everything
	destroy: function () {
		this.deactivate();
		this.clearAllMeasures();
		if (this._$dragHandle) {
			this._$dragHandle.remove();
			this._$dragHandle = null;
		}
		if (this._$tooltip) {
			this._$tooltip.remove();
			this._$tooltip = null;
		}
		if (this._$overlay) {
			this._$overlay.remove();
		}
		if (this._$canvas) {
			this._$canvas.remove();
		}
		// Cleanly unregister afterMapRequest event listener using stored proxy
		if (this._redrawProxy) {
			this._map.events.afterMapRequest.unregister(this._redrawProxy);
			this._redrawProxy = null;
		}

		$.Widget.prototype.destroy.apply(this, arguments); // default destroy
		$(this.element).data("mb_measure", null);
	}
});
