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

    if (typeof(RGraph) == 'undefined') RGraph = {};

    /**
    * The scatter graph constructor
    *
    * @param object canvas The cxanvas object
    * @param array  data   The chart data
    */
    RGraph.Scatter = function (id, data)
    {
        // Get the canvas and context objects
        this.id                = id;
        this.canvas            = document.getElementById(id);
        this.canvas.__object__ = this;
        this.context           = this.canvas.getContext ? this.canvas.getContext("2d") : null;
        this.max               = 0;
        this.coords            = [];
        this.data              = [];
        this.type              = 'scatter';
        this.isRGraph          = true;


        /**
        * Compatibility with older browsers
        */
        RGraph.OldBrowserCompat(this.context);


        // Various config properties
        this.properties = {
            'chart.background.barcolor1':   'white',
            'chart.background.barcolor2':   'white',
            'chart.background.grid':        true,
            'chart.background.grid.width':  1,
            'chart.background.grid.color':  '#ddd',
            'chart.background.grid.hsize':  20,
            'chart.background.grid.vsize':  20,
            'chart.background.hbars':       null,
            'chart.background.vbars':       null,
            'chart.background.grid.vlines': true,
            'chart.background.grid.hlines': true,
            'chart.background.grid.border': true,
            'chart.background.grid.autofit':false,
            'chart.background.grid.autofit.numhlines': 7,
            'chart.background.grid.autofit.numvlines': 20,
            'chart.text.size':              10,
            'chart.text.angle':             0,
            'chart.text.color':             'black',
            'chart.text.font':              'Verdana',
            'chart.tooltips.effect':         'fade',
            'chart.tooltips.hotspot':        3,
            'chart.tooltips.css.class':      'RGraph_tooltip',
            'chart.tooltips.highlight':     true,
            'chart.tooltips.coords.adjust':  [0,0],
            'chart.units.pre':              '',
            'chart.units.post':             '',
            'chart.tickmarks':              'cross',
            'chart.ticksize':               5,
            'chart.xticks':                 true,
            'chart.xaxis':                  true,
            'chart.gutter':                 25,
            'chart.xmax':                   0,
            'chart.ymax':                   null,
            'chart.ymin':                   null,
            'chart.scale.decimals':         null,
            'chart.scale.point':            '.',
            'chart.scale.thousand':         ',',
            'chart.title':                  '',
            'chart.title.background':       null,
            'chart.title.hpos':             null,
            'chart.title.vpos':             null,
            'chart.title.xaxis':            '',
            'chart.title.yaxis':            '',
            'chart.title.xaxis.pos':        0.25,
            'chart.title.yaxis.pos':        0.25,
            'chart.labels':                 [],
            'chart.ylabels':                true,
            'chart.ylabels.count':          5,
            'chart.contextmenu':            null,
            'chart.defaultcolor':           'black',
            'chart.xaxispos':               'bottom',
            'chart.yaxispos':               'left',
            'chart.noendxtick':             false,
            'chart.crosshairs':             false,
            'chart.crosshairs.color':       '#333',
            'chart.crosshairs.linewidth':   1,
            'chart.crosshairs.coords':      false,
            'chart.crosshairs.coords.fixed':true,
            'chart.crosshairs.coords.fadeout':false,
            'chart.crosshairs.coords.labels.x': 'X',
            'chart.crosshairs.coords.labels.y': 'Y',
            'chart.annotatable':            false,
            'chart.annotate.color':         'black',
            'chart.line':                   false,
            'chart.line.linewidth':         1,
            'chart.line.colors':            ['green', 'red'],
            'chart.line.shadow.color':      'rgba(0,0,0,0)',
            'chart.line.shadow.blur':       2,
            'chart.line.shadow.offsetx':    3,
            'chart.line.shadow.offsety':    3,
            'chart.line.stepped':           false,
            'chart.noaxes':                 false,
            'chart.key':                    [],
            'chart.key.background':         'white',
            'chart.key.position':           'graph',
            'chart.key.shadow':             false,
            'chart.key.shadow.color':       '#666',
            'chart.key.shadow.blur':        3,
            'chart.key.shadow.offsetx':     2,
            'chart.key.shadow.offsety':     2,
            'chart.key.position.gutter.boxed': true,
            'chart.key.position.x':         null,
            'chart.key.position.y':         null,
            'chart.key.color.shape':        'square',
            'chart.key.rounded':            true,
            'chart.axis.color':             'black',
            'chart.zoom.factor':            1.5,
            'chart.zoom.fade.in':           true,
            'chart.zoom.fade.out':          true,
            'chart.zoom.hdir':              'right',
            'chart.zoom.vdir':              'down',
            'chart.zoom.frames':            10,
            'chart.zoom.delay':             50,
            'chart.zoom.shadow':            true,
            'chart.zoom.mode':              'canvas',
            'chart.zoom.thumbnail.width':   75,
            'chart.zoom.thumbnail.height':  75,
            'chart.zoom.background':        true,
            'chart.zoom.action':            'zoom',
            'chart.boxplot.width':          8,
            'chart.resizable':              false,
            'chart.xmin':                   0
        }

        // Handle multiple datasets being given as one argument
        if (arguments[1][0] && arguments[1][0][0] && typeof(arguments[1][0][0][0]) == 'number') {
            // Store the data set(s)
            for (var i=0; i<arguments[1].length; ++i) {
                this.data[i] = arguments[1][i];
            }

        // Handle multiple data sets being supplied as seperate arguments
        } else {
            // Store the data set(s)
            for (var i=1; i<arguments.length; ++i) {
                this.data[i - 1] = arguments[i];
            }
        }

        // Check for support
        if (!this.canvas) {
            alert('[SCATTER] No canvas support');
            return;
        }

        // Check the common library has been included
        if (typeof(RGraph) == 'undefined') {
            alert('[SCATTER] Fatal error: The common library does not appear to have been included');
        }
    }


    /**
    * A simple setter
    *
    * @param string name  The name of the property to set
    * @param string value The value of the property
    */
    RGraph.Scatter.prototype.Set = function (name, value)
    {
        /**
        * This is here because the key expects a name of "chart.colors"
        */
        if (name == 'chart.line.colors') {
            this.properties['chart.colors'] = value;
        }

        /**
        * Allow compatibility with older property names
        */
        if (name == 'chart.tooltip.hotspot') {
            name = 'chart.tooltips.hotspot';
        }

        /**
        * chart.yaxispos should be left or right
        */
        if (name == 'chart.yaxispos' && value != 'left' && value != 'right') {
            alert("[SCATTER] chart.yaxispos should be left or right. You've set it to: '" + value + "' Changing it to left");
            value = 'left';
        }

        this.properties[name.toLowerCase()] = value;
    }


    /**
    * A simple getter
    *
    * @param string name  The name of the property to set
    */
    RGraph.Scatter.prototype.Get = function (name)
    {
        return this.properties[name];
    }


    /**
    * The function you call to draw the line chart
    */
    RGraph.Scatter.prototype.Draw = function ()
    {
        /**
        * Fire the onbeforedraw event
        */
        RGraph.FireCustomEvent(this, 'onbeforedraw');

        /**
        * Clear all of this canvases event handlers (the ones installed by RGraph)
        */
        RGraph.ClearEventListeners(this.id);

        // Go through all the data points and see if a tooltip has been given
        this.Set('chart.tooltips', false);
        this.hasTooltips = false;
        var overHotspot  = false;

        // Reset the coords array
        this.coords = [];

        if (!RGraph.isIE8()) {
            for (var i=0; i<this.data.length; ++i) {
                for (var j =0;j<this.data[i].length; ++j) {
                    if (this.data[i][j] && this.data[i][j][3] && typeof(this.data[i][j][3]) == 'string' && this.data[i][j][3].length) {
                        this.Set('chart.tooltips', [1]); // An array
                        this.hasTooltips = true;
                    }
                }
            }
        }

        // Reset the maximum value
        this.max = 0;

        // Work out the maximum Y value
        if (this.Get('chart.ymax') && this.Get('chart.ymax') > 0) {

            this.scale = [];
            this.max   = this.Get('chart.ymax');
            this.min   = this.Get('chart.ymin') ? this.Get('chart.ymin') : 0;

            this.scale[0] = ((this.max - this.min) * (1/5)) + this.min;
            this.scale[1] = ((this.max - this.min) * (2/5)) + this.min;
            this.scale[2] = ((this.max - this.min) * (3/5)) + this.min;
            this.scale[3] = ((this.max - this.min) * (4/5)) + this.min;
            this.scale[4] = ((this.max - this.min) * (5/5)) + this.min;

            var decimals = this.Get('chart.scale.decimals');

            this.scale = [
                          Number(this.scale[0]).toFixed(decimals),
                          Number(this.scale[1]).toFixed(decimals),
                          Number(this.scale[2]).toFixed(decimals),
                          Number(this.scale[3]).toFixed(decimals),
                          Number(this.scale[4]).toFixed(decimals)
                         ];

        } else {

            var i = 0;
            var j = 0;

            for (i=0; i<this.data.length; ++i) {
                for (j=0; j<this.data[i].length; ++j) {
                    this.max = Math.max(this.max, typeof(this.data[i][j][1]) == 'object' ? RGraph.array_max(this.data[i][j][1]) : Math.abs(this.data[i][j][1]));
                }
            }

            this.scale = RGraph.getScale(this.max, this);

            this.max   = this.scale[4];
            this.min   = this.Get('chart.ymin') ? this.Get('chart.ymin') : 0;

            if (this.min) {
                this.scale[0] = ((this.max - this.min) * (1/5)) + this.min;
                this.scale[1] = ((this.max - this.min) * (2/5)) + this.min;
                this.scale[2] = ((this.max - this.min) * (3/5)) + this.min;
                this.scale[3] = ((this.max - this.min) * (4/5)) + this.min;
                this.scale[4] = ((this.max - this.min) * (5/5)) + this.min;
            }


            if (typeof(this.Get('chart.scale.decimals')) == 'number') {
                var decimals = this.Get('chart.scale.decimals');

                this.scale = [
                              Number(this.scale[0]).toFixed(decimals),
                              Number(this.scale[1]).toFixed(decimals),
                              Number(this.scale[2]).toFixed(decimals),
                              Number(this.scale[3]).toFixed(decimals),
                              Number(this.scale[4]).toFixed(decimals)
                             ];
            }
        }

        this.grapharea = this.canvas.height - (2 * this.Get('chart.gutter'));

        // Progressively Draw the chart
        RGraph.background.Draw(this);

        /**
        * Draw any horizontal bars that have been specified
        */
        if (this.Get('chart.background.hbars') && this.Get('chart.background.hbars').length) {
            RGraph.DrawBars(this);
        }

        /**
        * Draw any vertical bars that have been specified
        */
        if (this.Get('chart.background.vbars') && this.Get('chart.background.vbars').length) {
            this.DrawVBars();
        }

        if (!this.Get('chart.noaxes')) {
            this.DrawAxes();
        }

        this.DrawLabels();

        i = 0;
        for(i=0; i<this.data.length; ++i) {
            this.DrawMarks(i);

            // Set the shadow
            this.context.shadowColor   = this.Get('chart.line.shadow.color');
            this.context.shadowOffsetX = this.Get('chart.line.shadow.offsetx');
            this.context.shadowOffsetY = this.Get('chart.line.shadow.offsety');
            this.context.shadowBlur    = this.Get('chart.line.shadow.blur');

            this.DrawLine(i);

            // Turn the shadow off
            RGraph.NoShadow(this);
        }


        if (this.Get('chart.line')) {
            for (var i=0;i<this.data.length; ++i) {
                this.DrawMarks(i); // Call this again so the tickmarks appear over the line
            }
        }



        /**
        * Setup the context menu if required
        */
        if (this.Get('chart.contextmenu')) {
            RGraph.ShowContext(this);
        }

        /**
        * Install the event handler for tooltips
        */
        if (this.hasTooltips) {

            /**
            * Register all charts
            */
            RGraph.Register(this);

            var overHotspot = false;

            var canvas_onmousemove_func = function (e)
            {
                e = RGraph.FixEventObject(e);

                var canvas      = e.target;
                var obj         = canvas.__object__;
                var context     = canvas.getContext('2d');
                var mouseCoords = RGraph.getMouseXY(e);
                var overHotspot = false;

                /**
                * Now loop through each point comparing the coords
                */

                var offset = obj.Get('chart.tooltips.hotspot'); // This is how far the hotspot extends

                for (var set=0; set<obj.coords.length; ++set) {
                    for (var i=0; i<obj.coords[set].length; ++i) {

                        var adjust = obj.Get('chart.tooltips.coords.adjust');
                        var xCoord = obj.coords[set][i][0] + adjust[0];
                        var yCoord = obj.coords[set][i][1] + adjust[1];
                        var tooltip = obj.coords[set][i][2];

                        if (mouseCoords[0] <= (xCoord + offset) &&
                            mouseCoords[0] >= (xCoord - offset) &&
                            mouseCoords[1] <= (yCoord + offset) &&
                            mouseCoords[1] >= (yCoord - offset) &&
                            tooltip &&
                            tooltip.length > 0) {

                            overHotspot = true;
                            canvas.style.cursor = 'pointer';

                            if (
                                !RGraph.Registry.Get('chart.tooltip') ||
                                RGraph.Registry.Get('chart.tooltip').__text__ != tooltip ||
                                RGraph.Registry.Get('chart.tooltip').__index__ != i ||
                                RGraph.Registry.Get('chart.tooltip').__dataset__ != set
                               ) {

                                if (obj.Get('chart.tooltips.highlight')) {
                                    RGraph.Redraw();
                                }

                                /**
                                * Get the tooltip text
                                */
                                if (typeof(tooltip) == 'function') {
                                    var text = String(tooltip(i));

                                } else {
                                    var text = String(tooltip);
                                }

                                RGraph.Tooltip(canvas, text, e.pageX, e.pageY, i);
                                RGraph.Registry.Get('chart.tooltip').__dataset__ = set;

                                /**
                                * Draw a circle around the mark
                                */
                                if (obj.Get('chart.tooltips.highlight')) {
                                    context.beginPath();
                                    context.fillStyle = 'rgba(255,255,255,0.5)';
                                    context.arc(xCoord, yCoord, 3, 0, 6.28, 0);
                                    context.fill();
                                }
                            }
                        }
                    }
                }

                /**
                * Reset the pointer
                */
                if (!overHotspot) {
                    canvas.style.cursor = 'default';
                }
            }
            this.canvas.addEventListener('mousemove', canvas_onmousemove_func, false);
            RGraph.AddEventListener(this.id, 'mousemove', canvas_onmousemove_func);
        }


        /**
        * Draw the key if necessary
        */
        if (this.Get('chart.key') && this.Get('chart.key').length) {
            RGraph.DrawKey(this, this.Get('chart.key'), this.Get('chart.line.colors'));
        }


        /**
        * Draw crosschairs
        */
        RGraph.DrawCrosshairs(this);

        /**
        * If the canvas is annotatable, do install the event handlers
        */
        if (this.Get('chart.annotatable')) {
            RGraph.Annotate(this);
        }

        /**
        * This bit shows the mini zoom window if requested
        */
        if (this.Get('chart.zoom.mode') == 'thumbnail' || this.Get('chart.zoom.mode') == 'area') {
            RGraph.ShowZoomWindow(this);
        }


        /**
        * This function enables resizing
        */
        if (this.Get('chart.resizable')) {
            RGraph.AllowResizing(this);
        }

        /**
        * Fire the RGraph ondraw event
        */
        RGraph.FireCustomEvent(this, 'ondraw');
    }


    /**
    * Draws the axes of the scatter graph
    */
    RGraph.Scatter.prototype.DrawAxes = function ()
    {
        var canvas      = this.canvas;
        var context     = this.context;
        var graphHeight = this.canvas.height - (this.Get('chart.gutter') * 2);
        var gutter      = this.Get('chart.gutter');

        context.beginPath();
        context.strokeStyle = this.Get('chart.axis.color');
        context.lineWidth   = 1;

        // Draw the Y axis
        if (this.Get('chart.yaxispos') == 'left') {
            context.moveTo(gutter, gutter);
            context.lineTo(gutter, this.canvas.height - gutter);
        } else {
            context.moveTo(canvas.width - gutter, gutter);
            context.lineTo(canvas.width - gutter, canvas.height - gutter);
        }


        // Draw the X axis
        if (this.Get('chart.xaxis')) {
            if (this.Get('chart.xaxispos') == 'center') {
                context.moveTo(gutter, canvas.height / 2);
                context.lineTo(canvas.width - gutter, canvas.height / 2);
            } else {
                context.moveTo(gutter, canvas.height - gutter);
                context.lineTo(canvas.width - gutter, canvas.height - gutter);
            }
        }

        /**
        * Draw the Y tickmarks
        */
        for (y=gutter; y < canvas.height - gutter + (this.Get('chart.xaxispos') == 'center' ? 1 : 0) ; y+=(graphHeight / 5) / 2) {

            // This is here to accomodate the X axis being at the center
            if (y == (canvas.height / 2) ) continue;

            if (this.Get('chart.yaxispos') == 'left') {
                context.moveTo(gutter, y);
                context.lineTo(gutter - 3, y);
            } else {
                context.moveTo(canvas.width - gutter +3, y);
                context.lineTo(canvas.width - gutter, y);
            }
        }


        /**
        * Draw the X tickmarks
        */
        if (this.Get('chart.xticks') && this.Get('chart.xaxis')) {

            var x  = 0;
            var y  =  (this.Get('chart.xaxispos') == 'center') ? (this.canvas.height / 2) : (this.canvas.height - gutter);
            this.xTickGap = (this.canvas.width - (2 * gutter) ) / this.Get('chart.labels').length;

            for (x = (gutter + (this.Get('chart.yaxispos') == 'left' ? this.xTickGap / 2 : 0) ); x<=(canvas.width - gutter - (this.Get('chart.yaxispos') == 'left' ? 0 : 1)); x += this.xTickGap / 2) {

                if (this.Get('chart.yaxispos') == 'left' && this.Get('chart.noendxtick') == true && x == (canvas.width - gutter) ) {
                    continue;

                } else if (this.Get('chart.yaxispos') == 'right' && this.Get('chart.noendxtick') == true && x == gutter) {
                    continue;
                }

                context.moveTo(x, y - (this.Get('chart.xaxispos') == 'center' ? 3 : 0));
                context.lineTo(x, y + 3);
            }
        }

        context.stroke();
    }


    /**
    * Draws the labels on the scatter graph
    */
    RGraph.Scatter.prototype.DrawLabels = function ()
    {
        this.context.fillStyle = this.Get('chart.text.color');
        var font       = this.Get('chart.text.font');
        var xMin       = this.Get('chart.xmin');
        var xMax       = this.Get('chart.xmax');
        var yMax       = this.scale[4];
        var gutter     = this.Get('chart.gutter');
        var text_size  = this.Get('chart.text.size');
        var units_pre  = this.Get('chart.units.pre');
        var units_post = this.Get('chart.units.post');
        var numYLabels = this.Get('chart.ylabels.count');
        var context    = this.context;
        var canvas     = this.canvas;

        this.halfTextHeight = text_size / 2;


        this.halfGraphHeight = (this.canvas.height - (2 * this.Get('chart.gutter'))) / 2;

        /**
        * Draw the Y yaxis labels, be it at the top or center
        */
        if (this.Get('chart.ylabels')) {

            var xPos  = this.Get('chart.yaxispos') == 'left' ? gutter - 5 : canvas.width - gutter + 5;
            var align = this.Get('chart.yaxispos') == 'right' ? 'left' : 'right';

            if (this.Get('chart.xaxispos') == 'center') {


                /**
                * Specific Y labels
                */
                if (typeof(this.Get('chart.ylabels.specific')) == 'object') {

                    var labels = this.Get('chart.ylabels.specific');

                    for (var i=0; i<this.Get('chart.ylabels.specific').length; ++i) {
                        var y = gutter + (i * (this.grapharea / (labels.length * 2) ) );
                        RGraph.Text(context, font, text_size, xPos, y, labels[i], 'center', align);
                    }

                    var reversed_labels = RGraph.array_reverse(labels);

                    for (var i=0; i<reversed_labels.length; ++i) {
                        var y = gutter + (this.grapharea / 2) + ((i+1) * (this.grapharea / (labels.length * 2) ) );

                        RGraph.Text(context, font, text_size, xPos, y, reversed_labels[i], 'center', align);
                    }

                    return;
                }


                if (numYLabels == 1 || numYLabels == 3 || numYLabels == 5) {
                    // Draw the top halves labels
                    RGraph.Text(context, font, text_size, xPos, gutter, RGraph.number_format(this, this.scale[4], units_pre, units_post), 'center', align);


                    if (numYLabels >= 5) {
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (1/10) ), RGraph.number_format(this, this.scale[3], units_pre, units_post), 'center', align);
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (3/10) ), RGraph.number_format(this, this.scale[1], units_pre, units_post), 'center', align);
                    }

                    if (numYLabels >= 3) {
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (2/10) ), RGraph.number_format(this, this.scale[2], units_pre, units_post), 'center', align);
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (4/10) ), RGraph.number_format(this, this.scale[0], units_pre, units_post), 'center', align);
                    }

                    // Draw the bottom halves labels
                    if (numYLabels >= 3) {
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (1/10) ) + this.halfGraphHeight, '-' + RGraph.number_format(this, this.scale[0], units_pre, units_post), 'center', align);
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (3/10) ) + this.halfGraphHeight, '-' + RGraph.number_format(this, this.scale[2], units_pre, units_post), 'center', align);
                    }

                    if (numYLabels == 5) {
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (2/10) ) + this.halfGraphHeight, '-' + RGraph.number_format(this, this.scale[1], units_pre, units_post), 'center', align);
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (4/10) ) + this.halfGraphHeight, '-' + RGraph.number_format(this, this.scale[3], units_pre, units_post), 'center', align);
                    }

                    RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (5/10) ) + this.halfGraphHeight, '-' + RGraph.number_format(this, this.scale[4], units_pre, units_post), 'center', align);

                } else if (numYLabels == 10) {
                    // 10 Y labels
                    var interval = (this.grapharea / numYLabels) / 2;

                    for (var i=0; i<numYLabels; ++i) {
                        RGraph.Text(context, font, text_size, xPos,gutter + ((canvas.height - (2 * gutter)) * (i/20) ),RGraph.number_format(this,

                        (this.max - (this.max * (i/10))).toFixed(this.Get('chart.scale.decimals')),

                        units_pre, units_post),'center', align);
                        RGraph.Text(context, font, text_size, xPos,gutter + ((canvas.height - (2 * gutter)) * (i/20) ) + (this.grapharea / 2) + (this.grapharea / 20),'-' + RGraph.number_format(this, ((this.max * (i/10)) + (this.max * (1/10))).toFixed((this.Get('chart.scale.decimals'))), units_pre, units_post), 'center', align);
                    }

                } else {
                    alert('[SCATTER SCALE] Number of Y labels can be 1/3/5/10 only');
                }

            } else {

                var xPos  = this.Get('chart.yaxispos') == 'left' ? gutter - 5 : canvas.width - gutter + 5;
                var align = this.Get('chart.yaxispos') == 'right' ? 'left' : 'right';

                /**
                * Specific Y labels
                */
                if (typeof(this.Get('chart.ylabels.specific')) == 'object') {

                    var labels = this.Get('chart.ylabels.specific');

                    for (var i=0; i<this.Get('chart.ylabels.specific').length; ++i) {
                        var y = gutter + (i * (this.grapharea / labels.length) );

                        RGraph.Text(context, font, text_size, xPos, y, labels[i], 'center', align);
                    }

                    return;
                }

                if (numYLabels == 1 || numYLabels == 3 || numYLabels == 5) {
                    RGraph.Text(context, font, text_size, xPos, gutter, RGraph.number_format(this, this.scale[4], units_pre, units_post), 'center', align);

                    if (numYLabels >= 5) {
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (1/5) ), RGraph.number_format(this, this.scale[3], units_pre, units_post), 'center', align);
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (3/5) ), RGraph.number_format(this, this.scale[1], units_pre, units_post), 'center', align);
                    }

                    if (numYLabels >= 3) {
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (2/5) ), RGraph.number_format(this, this.scale[2], units_pre, units_post), 'center', align);
                        RGraph.Text(context, font, text_size, xPos, gutter + ((canvas.height - (2 * gutter)) * (4/5) ), RGraph.number_format(this, this.scale[0], units_pre, units_post), 'center', align);
                    }
                } else if (numYLabels == 10) {

                    // 10 Y labels
                    var interval = (this.grapharea / numYLabels) / 2;

                    for (var i=0; i<numYLabels; ++i) {
                        RGraph.Text(context, font, text_size, xPos,gutter + ((canvas.height - (2 * gutter)) * (i/10) ),RGraph.number_format(this,(this.max - (this.max * (i/10))).toFixed((this.Get('chart.scale.decimals'))), units_pre, units_post), 'center', align);
                    }

                } else {
                    alert('[SCATTER SCALE] Number of Y labels can be 1/3/5/10 only');
                }

                if (this.Get('chart.ymin')) {
                    RGraph.Text(context, font, text_size, xPos, canvas.height - gutter,RGraph.number_format(this, this.Get('chart.ymin').toFixed(this.Get('chart.scale.decimals')), units_pre, units_post),'center', align);
                }
            }
        }

        // Put the text on the X axis
        var graphArea = this.canvas.width - (2 * gutter);
        var xInterval = graphArea / this.Get('chart.labels').length;
        var xPos      = gutter;
        var yPos      = (this.canvas.height - gutter) + 15;
        var labels    = this.Get('chart.labels');

        /**
        * Text angle
        */
        var angle  = 0;
        var valign = null;
        var halign = 'center';

        if (this.Get('chart.text.angle') > 0) {
            angle  = -1 * this.Get('chart.text.angle');
            valign = 'center';
            halign = 'right';
            yPos -= 10;
        }

        for (i=0; i<labels.length; ++i) {

            if (typeof(labels[i]) == 'object') {

                RGraph.Text(context, font, this.Get('chart.text.size'), gutter + (graphArea * ((labels[i][1] - xMin) / (this.Get('chart.xmax') - xMin))) + 5, yPos, String(labels[i][0]), valign, angle != 0 ? 'right' : 'left', null, angle);

                /**
                * Draw the gray indicator line
                */
                this.context.beginPath();
                    this.context.strokeStyle = '#bbb';
                    this.context.moveTo(gutter + (graphArea * ((labels[i][1] - xMin)/ (this.Get('chart.xmax') - xMin))), this.canvas.height - gutter);
                    this.context.lineTo(gutter + (graphArea * ((labels[i][1] - xMin)/ (this.Get('chart.xmax') - xMin))), this.canvas.height - gutter + 20);
                this.context.stroke();

            } else {
                RGraph.Text(context, font, this.Get('chart.text.size'), xPos + (this.xTickGap / 2), yPos, String(labels[i]), valign, halign, null, angle);
            }

            // Do this for the next time around
            xPos += xInterval;
        }
    }


    /**
    * Draws the actual scatter graph marks
    *
    * @param i integer The dataset index
    */
    RGraph.Scatter.prototype.DrawMarks = function (i)
    {
        /**
        *  Reset the coords array
        */
        this.coords[i] = [];

        /**
        * Plot the values
        */
        var xmax          = this.Get('chart.xmax');
        var default_color = this.Get('chart.defaultcolor');

        for (var j=0; j<this.data[i].length; ++j) {
            /**
            * This is here because tooltips are optional
            */
            var data_point = this.data[i];

            var xCoord = data_point[j][0];
            var yCoord = data_point[j][1];
            var color  = data_point[j][2] ? data_point[j][2] : default_color;
            var tooltip = (data_point[j] && data_point[j][3]) ? data_point[j][3] : null;


            this.DrawMark(
                          i,
                          xCoord,
                          yCoord,
                          xmax,
                          this.scale[4],
                          color,
                          tooltip,
                          this.coords[i],
                          data_point
                         );
        }
    }


    /**
    * Draws a single scatter mark
    */
    RGraph.Scatter.prototype.DrawMark = function (index, x, y, xMax, yMax, color, tooltip, coords, data)
    {
        var tickmarks = this.Get('chart.tickmarks');
        var tickSize  = this.Get('chart.ticksize');
        var gutter    = this.Get('chart.gutter');
        var xMin      = this.Get('chart.xmin');
        var x = ((x - xMin) / (xMax - xMin)) * (this.canvas.width - (2 * gutter));
        var originalX = x;
        var originalY = y;

        /**
        * This allows chart.tickmarks to be an array
        */

        if (tickmarks && typeof(tickmarks) == 'object') {
            tickmarks = tickmarks[index];
        }


        /**
        * This allows chart.ticksize to be an array
        */
        if (typeof(tickSize) == 'object') {
            var tickSize     = tickSize[index];
            var halfTickSize = tickSize / 2;
        } else {
            var halfTickSize = tickSize / 2;
        }


        /**
        * This bit is for boxplots only
        */
        if (   typeof(y) == 'object'
            && typeof(y[0]) == 'number'
            && typeof(y[1]) == 'number'
            && typeof(y[2]) == 'number'
            && typeof(y[3]) == 'number'
            && typeof(y[4]) == 'number'
           ) {

            var yMin = this.Get('chart.ymin') ? this.Get('chart.ymin') : 0;
            this.Set('chart.boxplot', true);
            this.graphheight = this.canvas.height - (2 * gutter);

            if (this.Get('chart.xaxispos') == 'center') {
                this.graphheight /= 2;
            }

            var y0 = (this.graphheight) - ((y[4] - yMin) / (yMax - yMin)) * (this.graphheight);
            var y1 = (this.graphheight) - ((y[3] - yMin) / (yMax - yMin)) * (this.graphheight);
            var y2 = (this.graphheight) - ((y[2] - yMin) / (yMax - yMin)) * (this.graphheight);
            var y3 = (this.graphheight) - ((y[1] - yMin) / (yMax - yMin)) * (this.graphheight);
            var y4 = (this.graphheight) - ((y[0] - yMin) / (yMax - yMin)) * (this.graphheight);

            var col1  = y[5];
            var col2  = y[6];

            // Override the boxWidth
            if (typeof(y[7]) == 'number') {
                var boxWidth = y[7];
            }

            var y = this.graphheight - y2;

        } else {
            var yMin = this.Get('chart.ymin') ? this.Get('chart.ymin') : 0;
            var y = (( (y - yMin) / (yMax - yMin)) * (this.canvas.height - (2 * gutter)));
        }

        /**
        * Account for the X axis being at the centre
        */
        if (this.Get('chart.xaxispos') == 'center') {
            y /= 2;
            y += this.halfGraphHeight;
        }

        // This is so that points are on the graph, and not the gutter
        x += gutter;
        y = this.canvas.height - gutter - y;

        this.context.beginPath();

        // Color
        this.context.strokeStyle = color;

        /**
        * Boxplots
        */
        if (   this.Get('chart.boxplot')
            && typeof(y0) == 'number'
            && typeof(y1) == 'number'
            && typeof(y2) == 'number'
            && typeof(y3) == 'number'
            && typeof(y4) == 'number'
           ) {

            var boxWidth = boxWidth ? boxWidth : this.Get('chart.boxplot.width');
            var halfBoxWidth = boxWidth / 2;

            this.context.beginPath();

            // Draw the upper coloured box if a value is specified
            if (col1) {
                this.context.fillStyle = col1;
                this.context.fillRect(x - halfBoxWidth, y1 + gutter, boxWidth, y2 - y1);
            }

            // Draw the lower coloured box if a value is specified
            if (col2) {
                this.context.fillStyle = col2;
                this.context.fillRect(x - halfBoxWidth, y2 + gutter, boxWidth, y3 - y2);
            }

            this.context.strokeRect(x - halfBoxWidth, y1 + gutter, boxWidth, y3 - y1);
            this.context.stroke();

            // Now draw the whiskers
            this.context.beginPath();
            this.context.moveTo(x - halfBoxWidth, y0 + gutter);
            this.context.lineTo(x + halfBoxWidth, y0 + gutter);

            this.context.moveTo(x, y0 + gutter);
            this.context.lineTo(x, y1 + gutter);

            this.context.moveTo(x - halfBoxWidth, y4 + gutter);
            this.context.lineTo(x + halfBoxWidth, y4 + gutter);

            this.context.moveTo(x, y4 + gutter);
            this.context.lineTo(x, y3 + gutter);

            this.context.stroke();
        }


        /**
        * Draw the tickmark, but not for boxplots
        */

        if (!y0 && !y1 && !y2 && !y3 && !y4) {

            this.graphheight = this.canvas.height - (2 * gutter);



            if (tickmarks == 'circle') {
                this.context.arc(x, y, halfTickSize, 0, 6.28, 0);
                this.context.fillStyle = color;
                this.context.fill();

            } else if (tickmarks == 'plus') {

                this.context.moveTo(x, y - halfTickSize);
                this.context.lineTo(x, y + halfTickSize);
                this.context.moveTo(x - halfTickSize, y);
                this.context.lineTo(x + halfTickSize, y);
                this.context.stroke();

            } else if (tickmarks == 'square') {
                this.context.strokeStyle = color;
                this.context.fillStyle = color;
                this.context.fillRect(
                                      x - halfTickSize,
                                      y - halfTickSize,
                                      tickSize,
                                      tickSize
                                     );
                //this.context.fill();

            } else if (tickmarks == 'cross') {

                this.context.moveTo(x - halfTickSize, y - halfTickSize);
                this.context.lineTo(x + halfTickSize, y + halfTickSize);
                this.context.moveTo(x + halfTickSize, y - halfTickSize);
                this.context.lineTo(x - halfTickSize, y + halfTickSize);

                this.context.stroke();

            /**
            * Diamond shape tickmarks
            */
            } else if (tickmarks == 'diamond') {
                this.context.fillStyle = this.context.strokeStyle;

                this.context.moveTo(x, y - halfTickSize);
                this.context.lineTo(x + halfTickSize, y);
                this.context.lineTo(x, y + halfTickSize);
                this.context.lineTo(x - halfTickSize, y);
                this.context.lineTo(x, y - halfTickSize);

                this.context.fill();
                this.context.stroke();

            /**
            * Custom tickmark style
            */
            } else if (typeof(tickmarks) == 'function') {

                var graphWidth = this.canvas.width - (2 * this.Get('chart.gutter'))
                var xVal = ((x - this.Get('chart.gutter')) / graphWidth) * xMax;
                var yVal = ((this.graphheight - (y - this.Get('chart.gutter'))) / this.graphheight) * yMax;

                tickmarks(this, data, x, y, xVal, yVal, xMax, yMax, color)

            /**
            * No tickmarks
            */
            } else if (tickmarks == null) {

            /**
            * Unknown tickmark type
            */
            } else {
                alert('[SCATTER] (' + this.id + ') Unknown tickmark style: ' + tickmarks );
            }
        }

        /**
        * Add the tickmark to the coords array
        */
        coords.push([x, y, tooltip]);
    }


    /**
    * Draws an optional line connecting the tick marks.
    *
    * @param i The index of the dataset to use
    */
    RGraph.Scatter.prototype.DrawLine = function (i)
    {
        if (this.Get('chart.line') && this.coords[i].length >= 2) {

            this.context.lineCap     = 'round';
            this.context.lineJoin    = 'round';
            this.context.lineWidth   = this.GetLineWidth(i);// i is the index of the set of coordinates
            this.context.strokeStyle = this.Get('chart.line.colors')[i];
            this.context.beginPath();

            var len = this.coords[i].length;

            for (var j=0; j<this.coords[i].length; ++j) {

                var xPos = this.coords[i][j][0];
                var yPos = this.coords[i][j][1];

                if (j == 0) {
                    this.context.moveTo(xPos, yPos);
                } else {

                    // Stepped?
                    var stepped = this.Get('chart.line.stepped');

                    if (   (typeof(stepped) == 'boolean' && stepped)
                        || (typeof(stepped) == 'object' && stepped[i])
                       ) {
                        this.context.lineTo(this.coords[i][j][0], this.coords[i][j - 1][1]);
                    }

                    this.context.lineTo(xPos, yPos);
                }
            }

            this.context.stroke();
        }

        /**
        * Set the linewidth back to 1
        */
        this.context.lineWidth = 1;
    }


    /**
    * Returns the linewidth
    *
    * @param number i The index of the "line" (/set of coordinates)
    */
    RGraph.Scatter.prototype.GetLineWidth = function (i)
    {
        var linewidth = this.Get('chart.line.linewidth');

        if (typeof(linewidth) == 'number') {
            return linewidth;

        } else if (typeof(linewidth) == 'object') {
            if (linewidth[i]) {
                return linewidth[i];
            } else {
                return linewidth[0];
            }

            alert('[SCATTER] Error! chart.linewidth should be a single number or an array of one or more numbers');
        }
    }


    /**
    * Draws vertical bars. Line chart doesn't use a horizontal scale, hence this function
    * is not common
    */
    RGraph.Scatter.prototype.DrawVBars = function ()
    {
        var canvas  = this.canvas;
        var context = this.context;
        var vbars = this.Get('chart.background.vbars');
        var gutter = this.Get('chart.gutter');
        var graphWidth = canvas.width - gutter - gutter;

        if (vbars) {

            var xmax = this.Get('chart.xmax');

            for (var i=0; i<vbars.length; ++i) {
                var startX = ((vbars[i][0] / xmax) * graphWidth) + gutter;
                var width  = (vbars[i][1] / xmax) * graphWidth;

                context.beginPath();
                    context.fillStyle = vbars[i][2];
                    context.fillRect(startX, gutter, width, (canvas.height - gutter - gutter));
                context.fill();
            }
        }
    }