    /**
    * o------------------------------------------------------------------------------o
    * | This file is part of the RGraph package - you can learn more at:             |
    * |                                                                              |
    * |                          http://www.rgraph.net                               |
    * |                                                                              |
    * | This package is licensed under the RGraph license. For all kinds of business |
    * | purposes there is a small one-time licensing fee to pay and for non          |
    * | commercial  purposes it is free to use. You can read the full license here:  |
    * |                                                                              |
    * |                      http://www.rgraph.net/LICENSE.txt                       |
    * o------------------------------------------------------------------------------o
    */

    if (typeof(RGraph) == 'undefined') RGraph = {isRGraph:true,type:'common'};

    /**
    * This is used in two functions, hence it's here
    */
    RGraph.tooltips = {};
    RGraph.tooltips.padding   = '3px';
    RGraph.tooltips.font_face = 'Tahoma';
    RGraph.tooltips.font_size = '10pt';


    /**
    * Shows a tooltip next to the mouse pointer
    *
    * @param canvas object The canvas element object
    * @param text   string The tooltip text
    * @param int     x      The X position that the tooltip should appear at. Combined with the canvases offsetLeft
    *                       gives the absolute X position
    * @param int     y      The Y position the tooltip should appear at. Combined with the canvases offsetTop
    *                       gives the absolute Y position
    * @param int     idx    The index of the tooltip in the graph objects tooltip array
    */
    RGraph.Tooltip = function (canvas, text, x, y, idx)
    {
        /**
        * chart.tooltip.override allows you to totally take control of rendering the tooltip yourself
        */
        if (typeof(canvas.__object__.Get('chart.tooltips.override')) == 'function') {
            return canvas.__object__.Get('chart.tooltips.override')(canvas, text, x, y, idx);
        }

        /**
        * This facilitates the "id:xxx" format
        */
        text = RGraph.getTooltipText(text);

        /**
        * First clear any exising timers
        */
        var timers = RGraph.Registry.Get('chart.tooltip.timers');

        if (timers && timers.length) {
            for (i=0; i<timers.length; ++i) {
                clearTimeout(timers[i]);
            }
        }
        RGraph.Registry.Set('chart.tooltip.timers', []);

        /**
        * Hide the context menu if it's currently shown
        */
        if (canvas.__object__.Get('chart.contextmenu')) {
            RGraph.HideContext();
        }

        // Redraw the canvas?
        if (canvas.__object__.Get('chart.tooltips.highlight')) {
            RGraph.Redraw(canvas.id);
        }

        var effect = canvas.__object__.Get('chart.tooltips.effect').toLowerCase();

        if (effect == 'snap' && RGraph.Registry.Get('chart.tooltip')) {

            if (   canvas.__object__.type == 'line'
                || canvas.__object__.type == 'tradar'
                || canvas.__object__.type == 'scatter'
                || canvas.__object__.type == 'rscatter'
                ) {

                var tooltipObj = RGraph.Registry.Get('chart.tooltip');

                tooltipObj.style.width  = null;
                tooltipObj.style.height = null;

                tooltipObj.innerHTML = text;
                tooltipObj.__text__  = text;

                /**
                * Now that the new content has been set, re-set the width & height
                */
                RGraph.Registry.Get('chart.tooltip').style.width  = RGraph.getTooltipWidth(text, canvas.__object__) + 'px';
                RGraph.Registry.Get('chart.tooltip').style.height = RGraph.Registry.Get('chart.tooltip').offsetHeight - (RGraph.isIE9up() ? 7 : 0) + 'px';


                var currentx = parseInt(RGraph.Registry.Get('chart.tooltip').style.left);
                var currenty = parseInt(RGraph.Registry.Get('chart.tooltip').style.top);

                var diffx = x - currentx - ((x + RGraph.Registry.Get('chart.tooltip').offsetWidth) > document.body.offsetWidth ? RGraph.Registry.Get('chart.tooltip').offsetWidth : 0);
                var diffy = y - currenty - RGraph.Registry.Get('chart.tooltip').offsetHeight;

                // Position the tooltip
                setTimeout('RGraph.Registry.Get("chart.tooltip").style.left = "' + (currentx + (diffx * 0.2)) + 'px"', 25);
                setTimeout('RGraph.Registry.Get("chart.tooltip").style.left = "' + (currentx + (diffx * 0.4)) + 'px"', 50);
                setTimeout('RGraph.Registry.Get("chart.tooltip").style.left = "' + (currentx + (diffx * 0.6)) + 'px"', 75);
                setTimeout('RGraph.Registry.Get("chart.tooltip").style.left = "' + (currentx + (diffx * 0.8)) + 'px"', 100);
                setTimeout('RGraph.Registry.Get("chart.tooltip").style.left = "' + (currentx + (diffx * 1.0)) + 'px"', 125);

                setTimeout('RGraph.Registry.Get("chart.tooltip").style.top = "' + (currenty + (diffy * 0.2)) + 'px"', 25);
                setTimeout('RGraph.Registry.Get("chart.tooltip").style.top = "' + (currenty + (diffy * 0.4)) + 'px"', 50);
                setTimeout('RGraph.Registry.Get("chart.tooltip").style.top = "' + (currenty + (diffy * 0.6)) + 'px"', 75);
                setTimeout('RGraph.Registry.Get("chart.tooltip").style.top = "' + (currenty + (diffy * 0.8)) + 'px"', 100);
                setTimeout('RGraph.Registry.Get("chart.tooltip").style.top = "' + (currenty + (diffy * 1.0)) + 'px"', 125);

            } else {

                alert('[TOOLTIPS] The "snap" effect is only supported on the Line, Rscatter, Scatter and Tradar charts');
            }

            /**
            * Fire the tooltip event
            */
            RGraph.FireCustomEvent(canvas.__object__, 'ontooltip');

            return;
        }

        /**
        * Hide any currently shown tooltip
        */
        RGraph.HideTooltip();


        /**
        * Show a tool tip
        */
        var tooltipObj  = document.createElement('DIV');
        tooltipObj.className             = canvas.__object__.Get('chart.tooltips.css.class');
        tooltipObj.style.display         = 'none';
        tooltipObj.style.position        = 'absolute';
        tooltipObj.style.left            = 0;
        tooltipObj.style.top             = 0;
        tooltipObj.style.backgroundColor = '#ffe';
        tooltipObj.style.color           = 'black';
        if (!document.all) tooltipObj.style.border = '1px solid rgba(0,0,0,0)';
        tooltipObj.style.visibility      = 'visible';
        tooltipObj.style.paddingLeft     = RGraph.tooltips.padding;
        tooltipObj.style.paddingRight    = RGraph.tooltips.padding;
        tooltipObj.style.fontFamily      = RGraph.tooltips.font_face;
        tooltipObj.style.fontSize        = RGraph.tooltips.font_size;
        tooltipObj.style.zIndex          = 3;
        tooltipObj.style.borderRadius    = '5px';
        tooltipObj.style.MozBorderRadius    = '5px';
        tooltipObj.style.WebkitBorderRadius = '5px';
        tooltipObj.style.WebkitBoxShadow    = 'rgba(96,96,96,0.5) 3px 3px 3px';
        tooltipObj.style.MozBoxShadow       = 'rgba(96,96,96,0.5) 3px 3px 3px';
        tooltipObj.style.boxShadow          = 'rgba(96,96,96,0.5) 3px 3px 3px';
        tooltipObj.style.filter             = 'progid:DXImageTransform.Microsoft.Shadow(color=#666666,direction=135)';
        tooltipObj.style.opacity            = 0;
        tooltipObj.style.overflow           = 'hidden';
        tooltipObj.innerHTML                = text;
        tooltipObj.__text__                 = text; // This is set because the innerHTML can change when it's set
        tooltipObj.__canvas__               = canvas;
        tooltipObj.style.display            = 'inline';

        if (typeof(idx) == 'number') {
            tooltipObj.__index__ = idx;
        }

        document.body.appendChild(tooltipObj);

        var width  = tooltipObj.offsetWidth;
        var height = tooltipObj.offsetHeight - (RGraph.isIE9up() ? 7 : 0);

        if ((y - height - 2) > 0) {
            y = y - height - 2;
        } else {
            y = y + 2;
        }

        /**
        * Set the width on the tooltip so it doesn't resize if the window is resized
        */
        tooltipObj.style.width = width + 'px';
        //tooltipObj.style.height = 0; // Initially set the tooltip height to nothing

        /**
        * If the mouse is towards the right of the browser window and the tooltip would go outside of the window,
        * move it left
        */
        if ( (x + width) > document.body.offsetWidth ) {
            x             = x - width - 7;
            var placementLeft = true;

            if (canvas.__object__.Get('chart.tooltips.effect') == 'none') {
                x = x - 3;
            }

            tooltipObj.style.left    = x + 'px';
            tooltipObj.style.top     = y + 'px';

        } else {
            x += 5;

            tooltipObj.style.left = x + 'px';
            tooltipObj.style.top = y + 'px';
        }


        if (effect == 'expand') {

            tooltipObj.style.left        = (x + (width / 2)) + 'px';
            tooltipObj.style.top         = (y + (height / 2)) + 'px';
            leftDelta                    = (width / 2) / 10;
            topDelta                     = (height / 2) / 10;

            tooltipObj.style.width              = 0;
            tooltipObj.style.height             = 0;
            tooltipObj.style.boxShadow          = '';
            tooltipObj.style.MozBoxShadow       = '';
            tooltipObj.style.WebkitBoxShadow    = '';
            tooltipObj.style.borderRadius       = 0;
            tooltipObj.style.MozBorderRadius    = 0;
            tooltipObj.style.WebkitBorderRadius = 0;
            tooltipObj.style.opacity = 1;

            // Progressively move the tooltip to where it should be (the x position)
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) - leftDelta) + 'px' }", 25));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) - leftDelta) + 'px' }", 50));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) - leftDelta) + 'px' }", 75));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) - leftDelta) + 'px' }", 100));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) - leftDelta) + 'px' }", 125));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) - leftDelta) + 'px' }", 150));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) - leftDelta) + 'px' }", 175));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) - leftDelta) + 'px' }", 200));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) - leftDelta) + 'px' }", 225));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) - leftDelta) + 'px' }", 250));

            // Progressively move the tooltip to where it should be (the Y position)
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) - topDelta) + 'px' }", 25));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) - topDelta) + 'px' }", 50));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) - topDelta) + 'px' }", 75));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) - topDelta) + 'px' }", 100));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) - topDelta) + 'px' }", 125));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) - topDelta) + 'px' }", 150));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) - topDelta) + 'px' }", 175));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) - topDelta) + 'px' }", 200));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) - topDelta) + 'px' }", 225));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) - topDelta) + 'px' }", 250));

            // Progressively grow the tooltip width
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 0.1) + "px'; }", 25));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 0.2) + "px'; }", 50));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 0.3) + "px'; }", 75));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 0.4) + "px'; }", 100));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 0.5) + "px'; }", 125));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 0.6) + "px'; }", 150));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 0.7) + "px'; }", 175));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 0.8) + "px'; }", 200));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 0.9) + "px'; }", 225));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + width + "px'; }", 250));

            // Progressively grow the tooltip height
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 0.1) + "px'; }", 25));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 0.2) + "px'; }", 50));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 0.3) + "px'; }", 75));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 0.4) + "px'; }", 100));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 0.5) + "px'; }", 125));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 0.6) + "px'; }", 150));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 0.7) + "px'; }", 175));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 0.8) + "px'; }", 200));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 0.9) + "px'; }", 225));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + height + "px'; }", 250));

            // When the animation is finished, set the tooltip HTML
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').innerHTML = RGraph.Registry.Get('chart.tooltip').__text__; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.boxShadow = 'rgba(96,96,96,0.5) 3px 3px 3px'; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.MozBoxShadow = 'rgba(96,96,96,0.5) 3px 3px 3px'; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.WebkitBoxShadow = 'rgba(96,96,96,0.5) 3px 3px 3px'; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.borderRadius = '5px'; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.MozBorderRadius = '5px'; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.WebkitBorderRadius = '5px'; }", 250));

        } else if (effect == 'contract') {

            tooltipObj.style.left = (x - width) + 'px';
            tooltipObj.style.top  = (y - (height * 2)) + 'px';
            tooltipObj.style.cursor = 'pointer';

            leftDelta = width / 10;
            topDelta  = height / 10;

            tooltipObj.style.width  = (width * 5) + 'px';
            tooltipObj.style.height = (height * 5) + 'px';

            tooltipObj.style.opacity = 0.2;

            // Progressively move the tooltip to where it should be (the x position)
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) + leftDelta) + 'px' }", 25));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) + leftDelta) + 'px' }", 50));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) + leftDelta) + 'px' }", 75));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) + leftDelta) + 'px' }", 100));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) + leftDelta) + 'px' }", 125));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) + leftDelta) + 'px' }", 150));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) + leftDelta) + 'px' }", 175));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) + leftDelta) + 'px' }", 200));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) + leftDelta) + 'px' }", 225));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.left = (parseInt(RGraph.Registry.Get('chart.tooltip').style.left) + leftDelta) + 'px' }", 250));

            // Progressively move the tooltip to where it should be (the Y position)
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) + (topDelta*2)) + 'px' }", 25));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) + (topDelta*2)) + 'px' }", 50));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) + (topDelta*2)) + 'px' }", 75));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) + (topDelta*2)) + 'px' }", 100));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) + (topDelta*2)) + 'px' }", 125));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) + (topDelta*2)) + 'px' }", 150));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) + (topDelta*2)) + 'px' }", 175));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) + (topDelta*2)) + 'px' }", 200));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) + (topDelta*2)) + 'px' }", 225));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.top = (parseInt(RGraph.Registry.Get('chart.tooltip').style.top) + (topDelta*2)) + 'px' }", 250));

            // Progressively shrink the tooltip width
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 5.5) + "px'; }", 25));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 5.0) + "px'; }", 50));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 4.5) + "px'; }", 75));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 4.0) + "px'; }", 100));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 3.5) + "px'; }", 125));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 3.0) + "px'; }", 150));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 2.5) + "px'; }", 175));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 2.0) + "px'; }", 200));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + (width * 1.5) + "px'; }", 225));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.width = '" + width + "px'; }", 250));

            // Progressively shrink the tooltip height
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 5.5) + "px'; }", 25));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 5.0) + "px'; }", 50));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 4.5) + "px'; }", 75));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 4.0) + "px'; }", 100));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 3.5) + "px'; }", 125));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 3.0) + "px'; }", 150));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 2.5) + "px'; }", 175));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 2.0) + "px'; }", 200));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + (height * 1.5) + "px'; }", 225));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.height = '" + height + "px'; }", 250));

            // When the animation is finished, set the tooltip HTML
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').innerHTML = RGraph.Registry.Get('chart.tooltip').__text__; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.boxShadow = 'rgba(96,96,96,0.5) 3px 3px 3px'; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.MozBoxShadow = 'rgba(96,96,96,0.5) 3px 3px 3px'; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.WebkitBoxShadow = 'rgba(96,96,96,0.5) 3px 3px 3px'; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.borderRadius = '5px'; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.MozBorderRadius = '5px'; }", 250));
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.WebkitBorderRadius = '5px'; }", 250));

            /**
            * This resets the pointer
            */
            RGraph.Registry.Get('chart.tooltip.timers').push(setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.cursor = 'default'; }", 275));



        } else if (effect != 'fade' && effect != 'expand' && effect != 'none' && effect != 'snap' && effect != 'contract') {
            alert('[COMMON] Unknown tooltip effect: ' + effect);
        }

        if (effect != 'none') {
            setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.opacity = 0.1; RGraph.Registry.Get('chart.tooltip').style.border = '1px solid rgba(96,96,96,0.2)'; }", 25);
            setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.opacity = 0.2; RGraph.Registry.Get('chart.tooltip').style.border = '1px solid rgba(96,96,96,0.2)'; }", 50);
            setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.opacity = 0.3; RGraph.Registry.Get('chart.tooltip').style.border = '1px solid rgba(96,96,96,0.2)'; }", 75);
            setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.opacity = 0.4; RGraph.Registry.Get('chart.tooltip').style.border = '1px solid rgba(96,96,96,0.2)'; }", 100);
            setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.opacity = 0.5; RGraph.Registry.Get('chart.tooltip').style.border = '1px solid rgba(96,96,96,0.2)'; }", 125);
            setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.opacity = 0.6; RGraph.Registry.Get('chart.tooltip').style.border = '1px solid rgba(96,96,96,0.2)'; }", 150);
            setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.opacity = 0.7; RGraph.Registry.Get('chart.tooltip').style.border = '1px solid rgba(96,96,96,0.4)'; }", 175);
            setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.opacity = 0.8; RGraph.Registry.Get('chart.tooltip').style.border = '1px solid rgba(96,96,96,0.6)'; }", 200);
            setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.opacity = 0.9; RGraph.Registry.Get('chart.tooltip').style.border = '1px solid rgba(96,96,96,0.8)'; }", 225);
        }

        setTimeout("if (RGraph.Registry.Get('chart.tooltip')) { RGraph.Registry.Get('chart.tooltip').style.opacity = 1;RGraph.Registry.Get('chart.tooltip').style.border = '1px solid rgb(96,96,96)';}", effect == 'none' ? 50 : 250);

        /**
        * If the tooltip it self is clicked, cancel it
        */
        tooltipObj.onmousedown = function (e)
        {
            e = RGraph.FixEventObject(e)
            e.stopPropagation();
        }

        tooltipObj.onclick = function (e)
        {
            if (e.button == 0) {
                e = RGraph.FixEventObject(e);
                e.stopPropagation();
            }
        }

        /**
        * Install the function for hiding the tooltip.
        */
        document.body.onmousedown = function (event)
        {
            var tooltip = RGraph.Registry.Get('chart.tooltip');

            if (tooltip) {
                RGraph.HideTooltip();

                // Redraw if highlighting is enabled
                if (tooltip.__canvas__.__object__.Get('chart.tooltips.highlight')) {
                    RGraph.Redraw();
                }
            }
        }

        /**
        * If the window is resized, hide the tooltip
        */
        window.onresize = function ()
        {
            var tooltip = RGraph.Registry.Get('chart.tooltip');

            if (tooltip) {
                tooltip.parentNode.removeChild(tooltip);
                tooltip.style.display = 'none';
                tooltip.style.visibility = 'hidden';
                RGraph.Registry.Set('chart.tooltip', null);

                // Redraw the graph if necessary
                if (canvas.__object__.Get('chart.tooltips.highlight')) {
                    RGraph.Clear(canvas);
                    canvas.__object__.Draw();
                }
            }
        }

        /**
        * Keep a reference to the tooltip
        */
        RGraph.Registry.Set('chart.tooltip', tooltipObj);

        /**
        * Fire the tooltip event
        */
        RGraph.FireCustomEvent(canvas.__object__, 'ontooltip');
    }


    /**
    *
    */
    RGraph.getTooltipText = function (text)
    {
        var result = /^id:(.*)/.exec(text);

        if (result) {
            text = document.getElementById(result[1]).innerHTML;
        }

        return text;
    }


    /**
    *
    */
    RGraph.getTooltipWidth = function (text, obj)
    {
        var div = document.createElement('DIV');
            div.className             = obj.Get('chart.tooltips.css.class');
            div.style.paddingLeft     = RGraph.tooltips.padding;
            div.style.paddingRight    = RGraph.tooltips.padding;
            div.style.fontFamily      = RGraph.tooltips.font_face;
            div.style.fontSize        = RGraph.tooltips.font_size;
            div.style.visibility      = 'hidden';
            div.style.position        = 'absolute';
            div.style.top            = '300px';
            div.style.left             = 0;
            div.style.display         = 'inline';
            div.innerHTML             = RGraph.getTooltipText(text);
        document.body.appendChild(div);

        return div.offsetWidth;
    }


    /**
    * Hides the currently shown tooltip
    */
    RGraph.HideTooltip = function ()
    {
        var tooltip = RGraph.Registry.Get('chart.tooltip');

        if (tooltip) {
            tooltip.parentNode.removeChild(tooltip);
            tooltip.style.display = 'none';
            tooltip.style.visibility = 'hidden';
            RGraph.Registry.Set('chart.tooltip', null);
        }
    }