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

    function movestart(e) {
        var btn = e.which || (e.button !== undefined ? (e.button === 1 ? 2 : (e.button === 0 ? 1 : 3)) : 1);
        if (btn !== 1 && btn !== 2) {
            return;
        }
        mb_panActive = true;
        startPos = map.getMousePosition(e);
        stopPos = new Point(startPos);
        isDragging = false;
        $(document)
            .bind("mouseup", moveend)
            .bind("mousemove", move);
        if (btn === 2) {
            e.preventDefault();
            return false;
        }
    }

    function move(e) {
        if (!mb_panActive) {
            return false;
        }
        stopPos = map.getMousePosition(e);
        var dif = stopPos.minus(startPos);
        if (!isDragging && Math.abs(dif.x) < 4 && Math.abs(dif.y) < 4) {
            return;
        }
        $(map.getDomElement()).css('cursor', 'move'); // Set cursor to move
        map.moveMap(dif.x, dif.y);
        isDragging = true;

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
        mb_panActive = false;
        $(document)
            .unbind("mousemove", move)
            .unbind("mouseup", moveend);

        $(map.getDomElement()).css('cursor', 'default'); // Reset cursor

        // Zurücksetzen der Position des KML-Rendering-Pane
        var kmlPane = $('#kml-rendering-pane');
        if (kmlPane.length) {
            kmlPane.css({
                transform: 'none'
            });
        }

        if (isDragging) {
            var mapEl = map.getDomElement();
            var preventClick = function (ev) {
                ev.stopPropagation();
                ev.preventDefault();
                mapEl.removeEventListener('click', preventClick, true);
            };
            mapEl.addEventListener('click', preventClick, true);
            setTimeout(function () {
                mapEl.removeEventListener('click', preventClick, true);
            }, 100);

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
            map.moveMap();
            if (typeof mod_featureInfo_event === 'function') {
                mod_featureInfo_event(e);
            }
        }
    }

    Mapbender.bindPanEvents = function() {
        //console.log('mod_pan.js[bindPanEvents{f} called(bindIfFalse): panEventsActive=' + panEventsActive);
        if (!panEventsActive) {
            $(map.getDomElement()).bind('mousedown', movestart)
                                  .bind('mouseleave', moveend);
            panEventsActive = true;            
        }
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

    Mapbender.bindPanEvents();

    // Center roller / middle click drag is always enabled for panning
    $(map.getDomElement()).bind('mousedown', function (e) {
        if (e.which === 2 && !mb_panActive) {
            e.preventDefault();
            movestart(e);
            return false;
        }
    }).bind('auxclick', function (e) {
        if (e.which === 2) {
            e.preventDefault();
            return false;
        }
    });
    
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
            mb_panActive = false;
        }
    });
});

