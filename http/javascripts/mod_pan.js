/**
 * Package: pan1
 *
 * Description:
 * panning tool for the map
 * 
 * Files:
 *  - http/javascripts/mod_pan.js
 *
 * SQL:
 * > <SQL for element> 
 * > 
 * > <SQL for element var> 
 *
 * Help:
 * http://www.mapbender.org/SelArea1
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

if (options.deactivateOnOpenDialog == "true") {
    options.deactivateOnOpenDialog = true;
} else {
    options.deactivateOnOpenDialog = false;
}

var that = this;

var isDragging = false;
var panEventsActive = false;

Mapbender.events.init.register(function () {
    var mb_panActive = false;
    var startPos, stopPos;
    var map = Mapbender.modules[options.target];
    var prevCursor = null;
    var panButton = null;

    var activePreventClick = null;
    var preventClickTimer = null;

    function clearPreventClick() {
        if (preventClickTimer !== null) {
            clearTimeout(preventClickTimer);
            preventClickTimer = null;
        }
        if (activePreventClick && map && map.getDomElement()) {
            map.getDomElement().removeEventListener('click', activePreventClick, true);
            activePreventClick = null;
        }
    }

    function movestart(e) {
        // Allow panning with primary left mouse button (1) or middle mouse button (2)
        if (e.which !== 1 && e.which !== 2) {
            return;
        }
        // Clear any leftover click-suppression listener from an earlier drag before handling a new press
        clearPreventClick();
        // Prevent native browser image drag-and-drop / text selection / middle-click autoscroll without blocking 'click'
        e.preventDefault();
        // Reset dragging state, active button, and saved cursor on every mousedown
        panButton = e.which;
        isDragging = false;
        prevCursor = null;
        mb_panActive = true;
        startPos = map.getMousePosition(e);
        stopPos = new Point(startPos);
        $(document)
            .unbind("mouseup", moveend)
            .unbind("mousemove", move)
            .bind("mouseup", moveend)
            .bind("mousemove", move);
    }

    function move(e) {
        if (!mb_panActive) {
            return false;
        }
        stopPos = map.getMousePosition(e);
        var dif = stopPos.minus(startPos);
        // Apply 6px threshold only for left-button drags so minor jitter during clicks does not pan; middle-button pans immediately
        if (panButton === 1 && !isDragging && Math.abs(dif.x) < 6 && Math.abs(dif.y) < 6) {
            return;
        }
        if (!isDragging) {
            // Save the active map cursor (e.g. 'crosshair' in Hoehenprofil, Measure, Digitize) before switching to 'move'
            var curCursor = map.getDomElement().style.cursor;
            prevCursor = (curCursor && curCursor !== 'move') ? curCursor : 'default';
            $(map.getDomElement()).css('cursor', 'move'); // Set cursor to move
            isDragging = true;
        }
        map.moveMap(dif.x, dif.y);

        // Aktualisiere die Position des KML-Rendering-Pane während des Pannens
        var kmlPane = $('#kml-rendering-pane');
        if (kmlPane.length) {
            kmlPane.css({
                transform: 'translate(' + dif.x + 'px, ' + dif.y + 'px)'
            });
        }

        if (!$.browser.msie){
            return true;
        }
        return false;
    }

    function moveend(e) {
        if (!mb_panActive) {
            return false;
        }
        if (!map) {
            return false;
        }
        var releasedButton = panButton;
        panButton = null;
        mb_panActive = false;
        $(document)
            .unbind("mousemove", move)
            .unbind("mouseup", moveend);

        // Restore the pre-drag cursor only if a pan drag changed it, preserving active tool cursors (e.g. 'crosshair')
        if (prevCursor !== null) {
            $(map.getDomElement()).css('cursor', prevCursor);
            prevCursor = null;
        }

        if (isDragging) {
            isDragging = false;
            // Zurücksetzen der Position des KML-Rendering-Pane only after an actual pan drag
            var kmlPane = $('#kml-rendering-pane');
            if (kmlPane.length) {
                kmlPane.css({
                    transform: 'none'
                });
            }
            // Suppress trailing native 'click' only on mouseup (mouseleave never fires click); clean up on next tick or next mousedown
            if (!e || e.type === "mouseup") {
                clearPreventClick();
                var mapEl = map.getDomElement();
                activePreventClick = function (ev) {
                    ev.stopPropagation();
                    ev.preventDefault();
                    clearPreventClick();
                };
                mapEl.addEventListener('click', activePreventClick, true);
                preventClickTimer = setTimeout(clearPreventClick, 0);
            }

            var dif = stopPos.minus(startPos);
            var widthHeight = new Mapbender.Point(
                map.getWidth(),
                map.getHeight()
            );
            var center = widthHeight.times(0.5).minus(dif);
            var realCenter = map.convertPixelToReal(center);
            map.moveMap();
            map.zoom(false, 1.0, realCenter);
            return false;
        } else {
            isDragging = false;
            // Static click: skip map.moveMap() (map never moved) and trigger FeatureInfo only for primary left-click
            if (releasedButton === 1 && typeof mod_featureInfo_event === 'function') {
                mod_featureInfo_event(e);
            }
        }
    }

    Mapbender.bindPanEvents = function() {
        // Cleanly ensure mousedown and mouseleave listeners are bound without duplicates
        $(map.getDomElement()).unbind('mousedown', movestart)
                              .unbind('mouseleave', moveend)
                              .bind('mousedown', movestart)
                              .bind('mouseleave', moveend);
        panEventsActive = true;
    }

    Mapbender.unbindPanEvents = function() {
        //console.log('mod_pan.js[unbindPanEvents{f} called(unbindIfTrue): panEventsActive=' + panEventsActive);
        if (panEventsActive) {
            $(map.getDomElement()).unbind('mousedown', movestart)
                                  .unbind('mouseleave', moveend);
            $(document).unbind('mousemove', move)
                       .unbind('mouseup', moveend);
            panEventsActive = false;
        }
    }

    // Prevent native HTML5 image drag-and-drop inside map container so clicks are never hijacked
    $(map.getDomElement()).bind('dragstart', function () {
        return false;
    });

    Mapbender.bindPanEvents();
    
    // Funktion zum Anzeigen des Overlays
    function showOverlay() {
        $('.dialog-overlay').each(function() {
            $(this).show();
        });
    }

    // Funktion zum Ausblenden des Overlays
    function hideOverlay() {
        $('.dialog-overlay').each(function() {
            $(this).hide();
        });
    }
    
        // Event-Listener für resizable und draggable hinzufügen, wenn das Overlay vorhanden ist
    $(document).bind("resizestart", ".ui-dialog", function() {
        var dialog = $(this);
        if (dialog.find('.dialog-overlay').length > 0) {
            if (panEventsActive) {
                Mapbender.unbindPanEvents();
                dialog.data('panEventsActive', true);
            } else {
                dialog.data('panEventsActive', false); 
            }
            showOverlay();
        }
    });

    $(document).bind("dragstart", ".ui-dialog", function() {
        var dialog = $(this);

        // Überprüfen, ob das Dragging von der Titelleiste ausgelöst wurde
        // und nicht vom Schließen-Button oder dessen Icon
        var $titleBar = dialog.find('.ui-dialog-titlebar');
        var $closeButton = dialog.find('.ui-dialog-titlebar-close');
        if (
            !$(event.target).is($titleBar) &&
            $(event.target).closest('.ui-dialog-titlebar').length === 0 ||
            $(event.target).is($closeButton) ||
            $(event.target).closest('.ui-dialog-titlebar-close').length > 0
        ) {
            // Dragging wurde nicht von der Titelleiste gestartet oder vom Schließen-Button, ignorieren
            return;
        }

        if (dialog.find('.dialog-overlay').length > 0) {
            if (panEventsActive) {
                Mapbender.unbindPanEvents();
                dialog.data('panEventsActive', true);
            } else {
                dialog.data('panEventsActive', false);
            }
            showOverlay();
        }
    });
     
    $(document).bind("resizestop", ".ui-dialog", function() {
        var dialog = $(this);
        if (dialog.find('.dialog-overlay').length > 0) {
            if (dialog.data('panEventsActive')) {
                Mapbender.bindPanEvents();
            }
            hideOverlay();
        }
    });
    
    $(document).bind("dragstop", ".ui-dialog", function() {
        var dialog = $(this);
        if (dialog.find('.dialog-overlay').length > 0) {
            if (dialog.data('panEventsActive')) {
                Mapbender.bindPanEvents();
            }
            hideOverlay();
        }
    });

    var button = new Mapbender.Button({
        domElement: that,
        over: options.src.replace(/_off/, "_over"),
        on: options.src.replace(/_off/, "_on"),
        off: options.src,
        name: options.id,
        go: function () {
            if (!map) {
                new Mb_exception(options.id + ": " +
                    options.target + " is not a map!");
                return;
            }
            Mapbender.bindPanEvents();
        },
        stop: function () {
            $("#pan1").removeClass("myOnClass");
            if (!map) {
                return false;
            }
            Mapbender.unbindPanEvents();
            clearPreventClick();
            panButton = null;
            mb_panActive = false;
        }
    });
});

