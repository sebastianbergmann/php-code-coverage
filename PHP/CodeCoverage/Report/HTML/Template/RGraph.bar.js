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
    * The bar chart constructor
    *
    * @param object canvas The canvas object
    * @param array  data   The chart data
    */
    RGraph.Bar = function (id, data)
    {
        // Get the canvas and context objects
        this.id                = id;
        this.canvas            = document.getElementById(id);
        this.context           = this.canvas.getContext ? this.canvas.getContext("2d") : null;
        this.canvas.__object__ = this;
        this.type              = 'bar';
        this.max               = 0;
        this.stackedOrGrouped  = false;
        this.isRGraph          = true;

        /**
        * Compatibility with older browsers
        */
        RGraph.OldBrowserCompat(this.context);


        // Various config type stuff
        this.properties = {
            'chart.background.barcolor1':   'rgba(0,0,0,0)',
            'chart.background.barcolor2':   'rgba(0,0,0,0)',
            'chart.background.grid':        true,
            'chart.background.grid.color':  '#ddd',
            'chart.background.grid.width':  1,
            'chart.background.grid.hsize':  20,
            'chart.background.grid.vsize':  20,
            'chart.background.grid.vlines': true,
            'chart.background.grid.hlines': true,
            'chart.background.grid.border': true,
            'chart.background.grid.autofit':false,
            'chart.background.grid.autofit.numhlines': 7,
            'chart.background.grid.autofit.numvlines': 20,
            'chart.ytickgap':               20,
            'chart.smallyticks':            3,
            'chart.largeyticks':            5,
            'chart.numyticks':              10,
            'chart.hmargin':                5,
            'chart.strokecolor':            '#666',
            'chart.axis.color':             'black',
            'chart.gutter':                 25,
            'chart.labels':                 null,
            'chart.labels.ingraph':         null,
            'chart.labels.above':           false,
            'chart.labels.above.decimals':  0,
            'chart.labels.above.size':      null,
            'chart.ylabels':                true,
            'chart.ylabels.count':          5,
            'chart.ylabels.inside':         false,
            'chart.xlabels.offset':         0,
            'chart.xaxispos':               'bottom',
            'chart.yaxispos':               'left',
            'chart.text.color':             'black',
            'chart.text.size':              10,
            'chart.text.angle':             0,
            'chart.text.font':              'Verdana',
            'chart.ymax':                   null,
            'chart.title':                  '',
            'chart.title.background':       null,
            'chart.title.hpos':             null,
            'chart.title.vpos':             null,
            'chart.title.xaxis':            '',
            'chart.title.yaxis':            '',
            'chart.title.xaxis.pos':        0.25,
            'chart.title.yaxis.pos':        0.25,
            'chart.colors':                 ['rgb(0,0,255)', '#0f0', '#00f', '#ff0', '#0ff', '#0f0'],
            'chart.grouping':               'grouped',
            'chart.variant':                'bar',
            'chart.shadow':                 false,
            'chart.shadow.color':           '#666',
            'chart.shadow.offsetx':         3,
            'chart.shadow.offsety':         3,
            'chart.shadow.blur':            3,
            'chart.tooltips':               null,
            'chart.tooltips.effect':        'fade',
            'chart.tooltips.css.class':     'RGraph_tooltip',
            'chart.tooltips.event':         'onclick',
            'chart.tooltips.coords.adjust': [0,0],
            'chart.tooltips.highlight':     true,
            'chart.background.hbars':       null,

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
            'chart.key.text.size':          10,

            'chart.contextmenu':            null,
            'chart.line':                   null,
            'chart.units.pre':              '',
            'chart.units.post':             '',
            'chart.scale.decimals':         0,
            'chart.scale.point':            '.',
            'chart.scale.thousand':         ',',
            'chart.crosshairs':             false,
            'chart.crosshairs.color':       '#333',
            'chart.linewidth':              1,
            'chart.annotatable':            false,
            'chart.annotate.color':         'black',
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
            'chart.resizable':              false,
            'chart.adjustable':             false
        }

        // Check for support
        if (!this.canvas) {
            alert('[BAR] No canvas support');
            return;
        }

        // Check the common library has been included
        if (typeof(RGraph) == 'undefined') {
            alert('[BAR] Fatal error: The common library does not appear to have been included');
        }

        /**
        * Determine whether the chart will contain stacked or grouped bars
        */
        for (i=0; i<data.length; ++i) {
            if (typeof(data[i]) == 'object') {
                this.stackedOrGrouped = true;
            }
        }

        // Store the data
        this.data = data;

        // Used to store the coords of the bars
        this.coords = [];
    }


    /**
    * A setter
    *
    * @param name  string The name of the property to set
    * @param value mixed  The value of the property
    */
    RGraph.Bar.prototype.Set = function (name, value)
    {
        name = name.toLowerCase();

        if (name == 'chart.labels.abovebar') {
            name = 'chart.labels.above';
        }

        if (name == 'chart.strokestyle') {
            name = 'chart.strokecolor';
        }

        this.properties[name] = value;
    }


    /**
    * A getter
    *
    * @param name  string The name of the property to get
    */
    RGraph.Bar.prototype.Get = function (name)
    {
        if (name == 'chart.labels.abovebar') {
            name = 'chart.labels.above';
        }

        return this.properties[name];
    }


    /**
    * The function you call to draw the bar chart
    */
    RGraph.Bar.prototype.Draw = function ()
    {
        /**
        * Fire the onbeforedraw event
        */
        RGraph.FireCustomEvent(this, 'onbeforedraw');

        /**
        * Clear all of this canvases event handlers (the ones installed by RGraph)
        */
        RGraph.ClearEventListeners(this.id);

        /**
        * Convert any null values to 0. Won't make any difference to the bar (as opposed to the line chart)
        */
        for (var i=0; i<this.data.length; ++i) {
            if (this.data[i] == null) {
                this.data[i] = 0;
            }
        }


        // Cache this in a class variable as it's used rather a lot
        this.gutter = this.Get('chart.gutter');

        /**
        * Check for tooltips and alert the user that they're not supported with pyramid charts
        */
        if (   (this.Get('chart.variant') == 'pyramid' || this.Get('chart.variant') == 'dot')
            && typeof(this.Get('chart.tooltips')) == 'object'
            && this.Get('chart.tooltips')
            && this.Get('chart.tooltips').length > 0) {

            alert('[BAR] (' + this.id + ') Sorry, tooltips are not supported with dot or pyramid charts');
        }

        /**
        * Stop the coords array from growing uncontrollably
        */
        this.coords = [];

        /**
        * Work out a few things. They need to be here because they depend on things you can change before you
        * call Draw() but after you instantiate the object
        */
        this.max            = 0;
        this.grapharea      = this.canvas.height - ( (2 * this.gutter));
        this.halfgrapharea  = this.grapharea / 2;
        this.halfTextHeight = this.Get('chart.text.size') / 2;

        // Progressively Draw the chart
        RGraph.background.Draw(this);


        //If it's a sketch chart variant, draw the axes first
        if (this.Get('chart.variant') == 'sketch') {
            this.DrawAxes();
            this.Drawbars();
        } else {
            this.Drawbars();
            this.DrawAxes();
        }

        this.DrawLabels();


        // Draw the key if necessary
        if (this.Get('chart.key').length) {
            RGraph.DrawKey(this, this.Get('chart.key'), this.Get('chart.colors'));
        }


        /**
        * Setup the context menu if required
        */
        if (this.Get('chart.contextmenu')) {
            RGraph.ShowContext(this);
        }


        /**
        * Is a line is defined, draw it
        */
        var line = this.Get('chart.line');

        if (line) {

            // Check the length of the data(s)
            if (line.original_data[0].length != this.data.length) {
                alert("[BAR] You're adding a line with a differing amount of data points to the bar chart - this is not permitted");
            }

            // Check the X axis positions
            if (this.Get('chart.xaxispos') != line.Get('chart.xaxispos')) {
                alert("[BAR] Using different X axis positions when combining the Bar and Line is not advised");
            }

            line.Set('chart.gutter', this.Get('chart.gutter'));
            line.Set('chart.noaxes', true);
            line.Set('chart.background.barcolor1', 'rgba(0,0,0,0)');
            line.Set('chart.background.barcolor2', 'rgba(0,0,0,0)');
            line.Set('chart.background.grid', false);
            line.Set('chart.ylabels', false);
            line.Set('chart.hmargin', (this.canvas.width - (2 * this.gutter)) / (line.original_data[0].length * 2));

            // If a custom yMax is set, use that
            if (this.Get('chart.ymax')) {
                line.Set('chart.ymax', this.Get('chart.ymax'));
            }

            line.Draw();
        }


        /**
        * Draw "in graph" labels
        */
        if (this.Get('chart.labels.ingraph')) {
            RGraph.DrawInGraphLabels(this);
        }

        /**
        * Draw crosschairs
        */
        if (this.Get('chart.crosshairs')) {
            RGraph.DrawCrosshairs(this);
        }

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
        * This function enables adjusting
        */
        if (this.Get('chart.adjustable')) {
            RGraph.AllowAdjusting(this);
        }

        /**
        * Fire the RGraph ondraw event
        */
        RGraph.FireCustomEvent(this, 'ondraw');
    }


    /**
    * Draws the charts axes
    */
    RGraph.Bar.prototype.DrawAxes = function ()
    {
        var gutter   = this.gutter;
        var xaxispos = this.Get('chart.xaxispos');
        var yaxispos = this.Get('chart.yaxispos');

        this.context.beginPath();
        this.context.strokeStyle = this.Get('chart.axis.color');
        this.context.lineWidth   = 1;

        // Draw the Y axis
        if (yaxispos == 'right') {
            this.context.moveTo(this.canvas.width - gutter, gutter);
            this.context.lineTo(this.canvas.width - gutter, this.canvas.height - gutter);
        } else {
            this.context.moveTo(gutter, gutter);
            this.context.lineTo(gutter, this.canvas.height - gutter);
        }

        // Draw the X axis
        this.context.moveTo(gutter, (xaxispos == 'center' ? this.canvas.height / 2 : this.canvas.height - gutter));
        this.context.lineTo(this.canvas.width - gutter, xaxispos == 'center' ? this.canvas.height / 2 : this.canvas.height - gutter);

        var numYTicks = this.Get('chart.numyticks');

        // Draw the Y tickmarks
        var yTickGap = (this.canvas.height - (2 * gutter)) / numYTicks;
        var xpos     = yaxispos == 'left' ? gutter : this.canvas.width - gutter;

        for (y=gutter;
             xaxispos == 'center' ? y <= (this.canvas.height - gutter) : y < (this.canvas.height - gutter);
             y += yTickGap) {

            if (xaxispos == 'center' && y == (this.canvas.height / 2)) continue;

            this.context.moveTo(xpos, y);
            this.context.lineTo(xpos + (yaxispos == 'left' ? -3 : 3), y);
        }

        // Draw the X tickmarks
        xTickGap = (this.canvas.width - (2 * gutter) ) / this.data.length;
        yStart   = this.canvas.height - gutter;
        yEnd     = (this.canvas.height - gutter) + 3;

        //////////////// X TICKS ////////////////

        // Now move the Y start end positions down if the axis is set to center
        if (xaxispos == 'center') {
            yStart = (this.canvas.height / 2) + 3;
            yEnd   = (this.canvas.height / 2) - 3;
        }

        for (x=gutter + (yaxispos == 'left' ? xTickGap : 0); x<this.canvas.width - gutter + (yaxispos == 'left' ? 5 : 0); x+=xTickGap) {
            this.context.moveTo(x, yStart);
            this.context.lineTo(x, yEnd);
        }

        //////////////// X TICKS ////////////////

        this.context.stroke();
    }


    /**
    * Draws the bars
    */
    RGraph.Bar.prototype.Drawbars = function ()
    {
        this.context.lineWidth   = this.Get('chart.linewidth');
        this.context.strokeStyle = this.Get('chart.strokecolor');
        this.context.fillStyle   = this.Get('chart.colors')[0];
        var prevX                = 0;
        var prevY                = 0;
        var gutter               = this.gutter;
        var decimals             = this.Get('chart.scale.decimals');

        /**
        * Work out the max value
        */
        if (this.Get('chart.ymax')) {
            this.max = this.Get('chart.ymax');

            this.scale = [
                          (this.max * (1/5)).toFixed(decimals),
                          (this.max * (2/5)).toFixed(decimals),
                          (this.max * (3/5)).toFixed(decimals),
                          (this.max * (4/5)).toFixed(decimals),
                          this.max.toFixed(decimals)
                         ];
        } else {
            for (i=0; i<this.data.length; ++i) {
                if (typeof(this.data[i]) == 'object') {
                    var value = this.Get('chart.grouping') == 'grouped' ? Number(RGraph.array_max(this.data[i], true)) : Number(RGraph.array_sum(this.data[i])) ;

                } else {
                    var value = Number(this.data[i]);
                }

                this.max = Math.max(Math.abs(this.max), Math.abs(value));
            }

            this.scale = RGraph.getScale(this.max, this);
            this.max   = this.scale[4];

            if (this.Get('chart.scale.decimals')) {
                var decimals = this.Get('chart.scale.decimals');

                this.scale[0] = Number(this.scale[0]).toFixed(decimals);
                this.scale[1] = Number(this.scale[1]).toFixed(decimals);
                this.scale[2] = Number(this.scale[2]).toFixed(decimals);
                this.scale[3] = Number(this.scale[3]).toFixed(decimals);
                this.scale[4] = Number(this.scale[4]).toFixed(decimals);
            }
        }

        /**
        * Draw horizontal bars here
        */
        if (this.Get('chart.background.hbars') && this.Get('chart.background.hbars').length > 0) {
            RGraph.DrawBars(this);
        }

        var variant = this.Get('chart.variant');

        /**
        * Draw the 3D axes is necessary
        */
        if (variant == '3d') {
            RGraph.Draw3DAxes(this);
        }

        /**
        * Get the variant once, and draw the bars, be they regular, stacked or grouped
        */

        // Get these variables outside of the loop
        var xaxispos      = this.Get('chart.xaxispos');
        var width         = (this.canvas.width - (2 * gutter) ) / this.data.length;
        var orig_height   = height;
        var hmargin       = this.Get('chart.hmargin');
        var shadow        = this.Get('chart.shadow');
        var shadowColor   = this.Get('chart.shadow.color');
        var shadowBlur    = this.Get('chart.shadow.blur');
        var shadowOffsetX = this.Get('chart.shadow.offsetx');
        var shadowOffsetY = this.Get('chart.shadow.offsety');
        var strokeStyle   = this.Get('chart.strokecolor');
        var colors        = this.Get('chart.colors');

        for (i=0; i<this.data.length; ++i) {

            // Work out the height
            //The width is up outside the loop
            var height      = (RGraph.array_sum(this.data[i]) / this.max) * (this.canvas.height - (2 * gutter) );

            // Half the height if the Y axis is at the center
            if (xaxispos == 'center') {
                height /= 2;
            }

            var x = (i * width) + gutter;
            var y = xaxispos == 'center' ? (this.canvas.height / 2) - height : this.canvas.height - height - gutter;


            // Account for negative lengths - Some browsers (eg Chrome) don't like a negative value
            if (height < 0) {
                y += height;
                height = Math.abs(height);
            }

            /**
            * Turn on the shadow if need be
            */
            if (shadow) {
                this.context.shadowColor   = shadowColor;
                this.context.shadowBlur    = shadowBlur;
                this.context.shadowOffsetX = shadowOffsetX;
                this.context.shadowOffsetY = shadowOffsetY;
            }

            /**
            * Draw the bar
            */
            this.context.beginPath();
                if (typeof(this.data[i]) == 'number') {

                    var barWidth = width - (2 * hmargin);

                    // Set the fill color
                    this.context.strokeStyle = strokeStyle;
                    this.context.fillStyle = colors[0];

                    if (variant == 'sketch') {

                        this.context.lineCap = 'round';

                        var sketchOffset = 3;

                        this.context.beginPath();

                        this.context.strokeStyle = colors[0];

                        // Left side
                        this.context.moveTo(x + hmargin + 2, y + height - 2);
                        this.context.lineTo(x + hmargin , y - 2);

                        // The top
                        this.context.moveTo(x + hmargin - 3, y + -2 + (this.data[i] < 0 ? height : 0));
                        this.context.bezierCurveTo(x + ((hmargin + width) * 0.33),y + 5 + (this.data[i] < 0 ? height - 10: 0),x + ((hmargin + width) * 0.66),y + 5 + (this.data[i] < 0 ? height - 10 : 0),x + hmargin + width + -1, y + 0 + (this.data[i] < 0 ? height : 0));


                        // The right side
                        this.context.moveTo(x + hmargin + width - 2, y + -2);
                        this.context.lineTo(x + hmargin + width - 3, y + height - 3);

                        for (var r=0.2; r<=0.8; r+=0.2) {
                            this.context.moveTo(x + hmargin + width + (r > 0.4 ? -1 : 3) - (r * width),y - 1);
                            this.context.lineTo(x + hmargin + width - (r > 0.4 ? 1 : -1) - (r * width), y + height + (r == 0.2 ? 1 : -2));
                        }

                        this.context.stroke();

                    // Regular bar
                    } else if (variant == 'bar' || variant == '3d' || variant == 'glass') {

                        if (document.all && shadow) {
                            this.DrawIEShadow([x + hmargin, y, barWidth, height]);
                        }

                        if (variant == 'glass') {
                            RGraph.filledCurvyRect(this.context, x + hmargin, y, barWidth, height, 3, this.data[i] > 0, this.data[i] > 0, this.data[i] < 0, this.data[i] < 0);
                            RGraph.strokedCurvyRect(this.context, x + hmargin, y, barWidth, height, 3, this.data[i] > 0, this.data[i] > 0, this.data[i] < 0, this.data[i] < 0);
                        } else {
                            this.context.strokeRect(x + hmargin, y, barWidth, height);
                            this.context.fillRect(x + hmargin, y, barWidth, height);
                        }


                        // This bit draws the text labels that appear above the bars if requested
                        if (this.Get('chart.labels.above')) {

                            // Turn off any shadow
                            if (shadow) {
                                RGraph.NoShadow(this);
                            }

                            var yPos = y - 3;

                            // Account for negative bars
                            if (this.data[i] < 0) {
                                yPos += height + 6 + (this.Get('chart.text.size') - 4);
                            }

                            this.context.fillStyle = this.Get('chart.text.color');
                            RGraph.Text(this.context, this.Get('chart.text.font'), typeof(this.Get('chart.labels.above.size')) == 'number' ? this.Get('chart.labels.above.size') : this.Get('chart.text.size') - 3, x + hmargin + (barWidth / 2), yPos, RGraph.number_format(this, Number(this.data[i]).toFixed(this.Get('chart.labels.above.decimals')),this.Get('chart.units.pre'), this.Get('chart.units.post')), null, 'center');
                        }

                        // 3D effect
                        if (variant == '3d') {

                            var prevStrokeStyle = this.context.strokeStyle;
                            var prevFillStyle   = this.context.fillStyle;

                            // Draw the top
                            this.context.beginPath();
                                this.context.moveTo(x + hmargin, y);
                                this.context.lineTo(x + hmargin + 10, y - 5);
                                this.context.lineTo(x + hmargin + 10 + barWidth, y - 5);
                                this.context.lineTo(x + hmargin + barWidth, y);
                            this.context.closePath();

                            this.context.stroke();
                            this.context.fill();

                            // Draw the right hand side
                            this.context.beginPath();
                                this.context.moveTo(x + hmargin + barWidth, y);
                                this.context.lineTo(x + hmargin + barWidth + 10, y - 5);
                                this.context.lineTo(x + hmargin + barWidth + 10, y + height - 5);
                                this.context.lineTo(x + hmargin + barWidth, y + height);
                            this.context.closePath();

                            this.context.stroke();
                            this.context.fill();

                            // Draw the darker top section
                            this.context.beginPath();
                                this.context.fillStyle = 'rgba(255,255,255,0.3)';
                                this.context.moveTo(x + hmargin, y);
                                this.context.lineTo(x + hmargin + 10, y - 5);
                                this.context.lineTo(x + hmargin + 10 + barWidth, y - 5);
                                this.context.lineTo(x + hmargin + barWidth, y);
                                this.context.lineTo(x + hmargin, y);
                            this.context.closePath();

                            this.context.stroke();
                            this.context.fill();

                            // Draw the darker right side section
                            this.context.beginPath();
                                this.context.fillStyle = 'rgba(0,0,0,0.4)';
                                this.context.moveTo(x + hmargin + barWidth, y);
                                this.context.lineTo(x + hmargin + barWidth + 10, y - 5);
                                this.context.lineTo(x + hmargin + barWidth + 10, y - 5 + height);
                                this.context.lineTo(x + hmargin + barWidth, y + height);
                                this.context.lineTo(x + hmargin + barWidth, y);
                            this.context.closePath();

                            this.context.stroke();
                            this.context.fill();

                            this.context.strokeStyle = prevStrokeStyle;
                            this.context.fillStyle   = prevFillStyle;

                        // Glass variant
                        } else if (variant == 'glass') {

                            var grad = this.context.createLinearGradient(
                                                                         x + hmargin,
                                                                         y,
                                                                         x + hmargin + (barWidth / 2),
                                                                         y
                                                                        );
                            grad.addColorStop(0, 'rgba(255,255,255,0.9)');
                            grad.addColorStop(1, 'rgba(255,255,255,0.5)');

                            this.context.beginPath();
                            this.context.fillStyle = grad;
                            this.context.fillRect(x + hmargin + 2,y + (this.data[i] > 0 ? 2 : 0),(barWidth / 2) - 2,height - 2);
                            this.context.fill();
                        }

                    // Dot chart
                    } else if (variant == 'dot') {

                        this.context.beginPath();
                        this.context.moveTo(x + (width / 2), y);
                        this.context.lineTo(x + (width / 2), y + height);
                        this.context.stroke();

                        this.context.beginPath();
                        this.context.fillStyle = this.Get('chart.colors')[i];
                        this.context.arc(x + (width / 2), y + (this.data[i] > 0 ? 0 : height), 2, 0, 6.28, 0);

                        // Set the colour for the dots
                        this.context.fillStyle = this.Get('chart.colors')[0];

                        this.context.stroke();
                        this.context.fill();

                    // Pyramid chart
                    } else if (variant == 'pyramid') {

                        this.context.beginPath();
                            var startY = (this.Get('chart.xaxispos') == 'center' ? (this.canvas.height / 2) : (this.canvas.height - this.Get('chart.gutter')));

                            this.context.moveTo(x + hmargin, startY);
                            this.context.lineTo(
                                                x + hmargin + (barWidth / 2),
                                                y + (this.Get('chart.xaxispos') == 'center' && (this.data[i] < 0) ? height : 0)
                                               );
                            this.context.lineTo(x + hmargin + barWidth, startY);

                        this.context.closePath();

                        this.context.stroke();
                        this.context.fill();

                    // Arrow chart
                    } else if (variant == 'arrow') {
                        var startY = (this.Get('chart.xaxispos') == 'center' ? (this.canvas.height / 2) : (this.canvas.height - this.gutter));

                        this.context.lineWidth = this.Get('chart.linewidth') ? this.Get('chart.linewidth') : 1;
                        this.context.lineCap = 'round';

                        this.context.beginPath();

                            this.context.moveTo(x + hmargin + (barWidth / 2), startY);
                            this.context.lineTo(x + hmargin + (barWidth / 2), y + (this.Get('chart.xaxispos') == 'center' && (this.data[i] < 0) ? height : 0));
                            this.context.arc(x + hmargin + (barWidth / 2),
                                             y + (this.Get('chart.xaxispos') == 'center' && (this.data[i] < 0) ? height : 0),
                                             5,
                                             this.data[i] > 0 ? 0.78 : 5.6,
                                             this.data[i] > 0 ? 0.79 : 5.48,
                                             this.data[i] < 0);

                            this.context.moveTo(x + hmargin + (barWidth / 2), y + (this.Get('chart.xaxispos') == 'center' && (this.data[i] < 0) ? height : 0));
                            this.context.arc(x + hmargin + (barWidth / 2),
                                             y + (this.Get('chart.xaxispos') == 'center' && (this.data[i] < 0) ? height : 0),
                                             5,
                                             this.data[i] > 0 ? 2.355 : 4,
                                             this.data[i] > 0 ? 2.4 : 3.925,
                                             this.data[i] < 0);

                        this.context.stroke();

                        this.context.lineWidth = 1;

                    // Unknown variant type
                    } else {
                        alert('[BAR] Warning! Unknown chart.variant: ' + variant);
                    }

                    this.coords.push([x + hmargin, y, width - (2 * hmargin), height]);


                /**
                * Stacked bar
                */
                } else if (typeof(this.data[i]) == 'object' && this.Get('chart.grouping') == 'stacked') {

                    var barWidth     = width - (2 * hmargin);
                    var redrawCoords = [];// Necessary to draw if the shadow is enabled
                    var startY       = 0;

                    for (j=0; j<this.data[i].length; ++j) {

                        // Stacked bar chart and X axis pos in the middle - poitless since negative values are not permitted
                        if (xaxispos == 'center') {
                            alert("[BAR] It's fruitless having the X axis position at the center on a stacked bar chart.");
                            return;
                        }

                        // Negative values not permitted for the stacked chart
                        if (this.data[i][j] < 0) {
                            alert('[BAR] Negative values are not permitted with a stacked bar chart. Try a grouped one instead.');
                            return;
                        }

                        // Set the fill and stroke colors
                        this.context.strokeStyle = strokeStyle
                        this.context.fillStyle = colors[j];

                        var height = (this.data[i][j] / this.max) * (this.canvas.height - (2 * this.gutter) );

                        // If the X axis pos is in the center, we need to half the  height
                        if (xaxispos == 'center') {
                            height /= 2;
                        }

                        var totalHeight = (RGraph.array_sum(this.data[i]) / this.max) * (this.canvas.height - hmargin - (2 * this.gutter));

                        /**
                        * Store the coords for tooltips
                        */
                        this.coords.push([x + hmargin, y, width - (2 * hmargin), height]);

                        // MSIE shadow
                        if (document.all && shadow) {
                            this.DrawIEShadow([x + hmargin, y, width - (2 * hmargin), height + 1]);
                        }

                        this.context.strokeRect(x + hmargin, y, width - (2 * hmargin), height);
                        this.context.fillRect(x + hmargin, y, width - (2 * hmargin), height);


                        if (j == 0) {
                            var startY = y;
                            var startX = x;
                        }

                        /**
                        * Store the redraw coords if the shadow is enabled
                        */
                        if (shadow) {
                            redrawCoords.push([x + hmargin, y, width - (2 * hmargin), height, colors[j]]);
                        }

                        /**
                        * Stacked 3D effect
                        */
                        if (variant == '3d') {

                            var prevFillStyle = this.context.fillStyle;
                            var prevStrokeStyle = this.context.strokeStyle;


                            // Draw the top side
                            if (j == 0) {
                                this.context.beginPath();
                                    this.context.moveTo(startX + hmargin, y);
                                    this.context.lineTo(startX + 10 + hmargin, y - 5);
                                    this.context.lineTo(startX + 10 + barWidth + hmargin, y - 5);
                                    this.context.lineTo(startX + barWidth + hmargin, y);
                                this.context.closePath();

                                this.context.fill();
                                this.context.stroke();
                            }

                            // Draw the side section
                            this.context.beginPath();
                                this.context.moveTo(startX + barWidth + hmargin, y);
                                this.context.lineTo(startX + barWidth + hmargin + 10, y - 5);
                                this.context.lineTo(startX + barWidth + + hmargin + 10, y - 5 + height);
                                this.context.lineTo(startX + barWidth + hmargin , y + height);
                            this.context.closePath();

                            this.context.fill();
                            this.context.stroke();

                            // Draw the darker top side
                            if (j == 0) {
                                this.context.fillStyle = 'rgba(255,255,255,0.3)';
                                this.context.beginPath();
                                    this.context.moveTo(startX + hmargin, y);
                                    this.context.lineTo(startX + 10 + hmargin, y - 5);
                                    this.context.lineTo(startX + 10 + barWidth + hmargin, y - 5);
                                    this.context.lineTo(startX + barWidth + hmargin, y);
                                this.context.closePath();

                                this.context.fill();
                                this.context.stroke();
                            }

                            // Draw the darker side section
                            this.context.fillStyle = 'rgba(0,0,0,0.4)';
                            this.context.beginPath();
                                this.context.moveTo(startX + barWidth + hmargin, y);
                                this.context.lineTo(startX + barWidth + hmargin + 10, y - 5);
                                this.context.lineTo(startX + barWidth + + hmargin + 10, y - 5 + height);
                                this.context.lineTo(startX + barWidth + hmargin , y + height);
                            this.context.closePath();

                            this.context.fill();
                            this.context.stroke();

                            this.context.strokeStyle = prevStrokeStyle;
                            this.context.fillStyle = prevFillStyle;
                        }

                        y += height;
                    }

                    // This bit draws the text labels that appear above the bars if requested
                    if (this.Get('chart.labels.above')) {

                        // Turn off any shadow
                        RGraph.NoShadow(this);

                        this.context.fillStyle = this.Get('chart.text.color');
                        RGraph.Text(this.context,this.Get('chart.text.font'),typeof(this.Get('chart.labels.above.size')) == 'number' ? this.Get('chart.labels.above.size') : this.Get('chart.text.size') - 3,startX + (barWidth / 2) + this.Get('chart.hmargin'),startY - (this.Get('chart.shadow') && this.Get('chart.shadow.offsety') < 0 ? 7 : 4),String(this.Get('chart.units.pre') + RGraph.array_sum(this.data[i]).toFixed(this.Get('chart.labels.above.decimals')) + this.Get('chart.units.post')),null,'center');

                        // Turn any shadow back on
                        if (shadow) {
                            this.context.shadowColor   = shadowColor;
                            this.context.shadowBlur    = shadowBlur;
                            this.context.shadowOffsetX = shadowOffsetX;
                            this.context.shadowOffsetY = shadowOffsetY;
                        }
                    }


                    /**
                    * Redraw the bars if the shadow is enabled due to hem being drawn from the bottom up, and the
                    * shadow spilling over to higher up bars
                    */
                    if (shadow) {

                        RGraph.NoShadow(this);

                        for (k=0; k<redrawCoords.length; ++k) {
                            this.context.strokeStyle = strokeStyle;
                            this.context.fillStyle = redrawCoords[k][4];
                            this.context.strokeRect(redrawCoords[k][0], redrawCoords[k][1], redrawCoords[k][2], redrawCoords[k][3]);
                            this.context.fillRect(redrawCoords[k][0], redrawCoords[k][1], redrawCoords[k][2], redrawCoords[k][3]);

                            this.context.stroke();
                            this.context.fill();
                        }

                        // Reset the redraw coords to be empty
                        redrawCoords = [];
                    }
                /**
                * Grouped bar
                */
                } else if (typeof(this.data[i]) == 'object' && this.Get('chart.grouping') == 'grouped') {

                    var redrawCoords = [];
                    this.context.lineWidth = this.Get('chart.linewidth');

                    for (j=0; j<this.data[i].length; ++j) {
                        // Set the fill and stroke colors
                        this.context.strokeStyle = strokeStyle;
                        this.context.fillStyle   = colors[j];

                        var individualBarWidth = (width - (2 * hmargin)) / this.data[i].length;
                        var height = (this.data[i][j] / this.max) * (this.canvas.height - (2 * this.gutter) );

                        // If the X axis pos is in the center, we need to half the  height
                        if (xaxispos == 'center') {
                            height /= 2;
                        }

                        var startX = x + hmargin + (j * individualBarWidth);
                        var startY = (xaxispos == 'bottom' ? this.canvas.height : (this.canvas.height / 2) + this.gutter) - this.gutter - height;

                        // Account for a bug in chrome that doesn't allow negative heights
                        if (height < 0) {
                            startY += height;
                            height = Math.abs(height);
                        }

                        /**
                        * Draw MSIE shadow
                        */
                        if (document.all && shadow) {
                            this.DrawIEShadow([startX, startY, individualBarWidth, height]);
                        }

                        this.context.strokeRect(startX, startY, individualBarWidth, height);
                        this.context.fillRect(startX, startY, individualBarWidth, height);
                        y += height;

                        // This bit draws the text labels that appear above the bars if requested
                        if (this.Get('chart.labels.above')) {

                            this.context.strokeStyle = 'rgba(0,0,0,0)';

                            // Turn off any shadow
                            if (shadow) {
                                RGraph.NoShadow(this);
                            }

                            var yPos = y - 3;

                            // Account for negative bars
                            if (this.data[i][j] < 0) {
                                yPos += height + 6 + (this.Get('chart.text.size') - 4);
                            }

                            this.context.fillStyle = this.Get('chart.text.color');
                            RGraph.Text(this.context,this.Get('chart.text.font'),typeof(this.Get('chart.labels.above.size')) == 'number' ? this.Get('chart.labels.above.size') : this.Get('chart.text.size') - 3,startX + (individualBarWidth / 2),startY - 2,RGraph.number_format(this, this.data[i][j].toFixed(this.Get('chart.labels.above.decimals'))),null,'center');

                            // Turn any shadow back on
                            if (shadow) {
                                this.context.shadowColor   = shadowColor;
                                this.context.shadowBlur    = shadowBlur;
                                this.context.shadowOffsetX = shadowOffsetX;
                                this.context.shadowOffsetY = shadowOffsetY;
                            }
                        }

                        /**
                        * Grouped 3D effect
                        */
                        if (variant == '3d') {
                            var prevFillStyle = this.context.fillStyle;
                            var prevStrokeStyle = this.context.strokeStyle;

                            // Draw the top side
                            this.context.beginPath();
                                this.context.moveTo(startX, startY);
                                this.context.lineTo(startX + 10, startY - 5);
                                this.context.lineTo(startX + 10 + individualBarWidth, startY - 5);
                                this.context.lineTo(startX + individualBarWidth, startY);
                            this.context.closePath();

                            this.context.fill();
                            this.context.stroke();

                            // Draw the side section
                            this.context.beginPath();
                                this.context.moveTo(startX + individualBarWidth, startY);
                                this.context.lineTo(startX + individualBarWidth + 10, startY - 5);
                                this.context.lineTo(startX + individualBarWidth + 10, startY - 5 + height);
                                this.context.lineTo(startX + individualBarWidth , startY + height);
                            this.context.closePath();

                            this.context.fill();
                            this.context.stroke();


                            // Draw the darker top side
                            this.context.fillStyle = 'rgba(255,255,255,0.3)';
                            this.context.beginPath();
                                this.context.moveTo(startX, startY);
                                this.context.lineTo(startX + 10, startY - 5);
                                this.context.lineTo(startX + 10 + individualBarWidth, startY - 5);
                                this.context.lineTo(startX + individualBarWidth, startY);
                            this.context.closePath();

                            this.context.fill();
                            this.context.stroke();

                            // Draw the darker side section
                            this.context.fillStyle = 'rgba(0,0,0,0.4)';
                            this.context.beginPath();
                                this.context.moveTo(startX + individualBarWidth, startY);
                                this.context.lineTo(startX + individualBarWidth + 10, startY - 5);
                                this.context.lineTo(startX + individualBarWidth + 10, startY - 5 + height);
                                this.context.lineTo(startX + individualBarWidth , startY + height);
                            this.context.closePath();

                            this.context.fill();
                            this.context.stroke();

                            this.context.strokeStyle = prevStrokeStyle;
                            this.context.fillStyle   = prevFillStyle;
                        }

                        this.coords.push([startX, startY, individualBarWidth, height]);

                        // Facilitate shadows going to the left
                        if (this.Get('chart.shadow')) {
                            redrawCoords.push([startX, startY, individualBarWidth, height]);
                        }
                    }

                    /**
                    * Redraw the bar if shadows are going to the left
                    */
                    if (redrawCoords.length) {

                        RGraph.NoShadow(this);

                        this.context.lineWidth = this.Get('chart.linewidth');

                        this.context.beginPath();
                            for (var j=0; j<redrawCoords.length; ++j) {

                                this.context.fillStyle   = this.Get('chart.colors')[j];
                                this.context.strokeStyle = this.Get('chart.strokecolor');

                                this.context.fillRect(redrawCoords[j][0], redrawCoords[j][1], redrawCoords[j][2], redrawCoords[j][3]);
                                this.context.strokeRect(redrawCoords[j][0], redrawCoords[j][1], redrawCoords[j][2], redrawCoords[j][3]);
                            }
                        this.context.fill();
                        this.context.stroke();

                        redrawCoords = [];
                    }
                }

            this.context.closePath();
        }

        /**
        * Turn off any shadow
        */
        RGraph.NoShadow(this);


        /**
        * Install the onclick event handler
        */
        if (this.Get('chart.tooltips')) {

            // Need to register this object for redrawing
            RGraph.Register(this);

            /**
            * Install the window onclick handler
            */
            var window_onclick_func = function (){RGraph.Redraw();};
            window.addEventListener('click', window_onclick_func, false);
            RGraph.AddEventListener('window_' + this.id, 'click', window_onclick_func);



            /**
            * If the cursor is over a hotspot, change the cursor to a hand. Bar chart tooltips can now
            * be based around the onmousemove event
            */
            canvas_onmousemove = function (e)
            {
                e = RGraph.FixEventObject(e);

                var canvas    = document.getElementById(e.target.id);
                var obj       = canvas.__object__;
                var barCoords = obj.getBar(e);

                /**
                * If there are bar coords AND the bar has height
                */
                if (barCoords && barCoords[4] > 0) {

                    /**
                    * Get the tooltip text
                    */
                    if (typeof(obj.Get('chart.tooltips')) == 'function') {
                        var text = String(obj.Get('chart.tooltips')(barCoords[5]));

                    } else if (typeof(obj.Get('chart.tooltips')) == 'object' && typeof(obj.Get('chart.tooltips')[barCoords[5]]) == 'function') {
                        var text = String(obj.Get('chart.tooltips')[barCoords[5]](barCoords[5]));

                    } else if (typeof(obj.Get('chart.tooltips')) == 'object' && (typeof(obj.Get('chart.tooltips')[barCoords[5]]) == 'string' || typeof(obj.Get('chart.tooltips')[barCoords[5]]) == 'number')) {
                        var text = String(obj.Get('chart.tooltips')[barCoords[5]]);

                    } else {
                        var text = null;
                    }

                    if (text) {
                        canvas.style.cursor = 'pointer';
                    } else {
                        canvas.style.cursor = 'default';
                    }

                    /**
                    * Hide the currently displayed tooltip if the index is the same
                    */
                    if (   RGraph.Registry.Get('chart.tooltip')
                        && RGraph.Registry.Get('chart.tooltip').__canvas__.id != obj.id
                        && obj.Get('chart.tooltips.event') == 'onmousemove') {

                        RGraph.Redraw();
                        RGraph.HideTooltip();
                    }

                    /**
                    * This facilitates the tooltips using the onmousemove event
                    */

                    if (   obj.Get('chart.tooltips.event') == 'onmousemove'
                        && (
                               (RGraph.Registry.Get('chart.tooltip') && RGraph.Registry.Get('chart.tooltip').__index__ != barCoords[5])
                            || !RGraph.Registry.Get('chart.tooltip')
                           )
                        && text) {
                        /**
                        * Show a tooltip if it's defined
                        */
                        RGraph.Redraw(obj);

                        obj.context.beginPath();
                        obj.context.strokeStyle = 'black';
                        obj.context.fillStyle   = 'rgba(255,255,255,0.5)';
                        obj.context.strokeRect(barCoords[1], barCoords[2], barCoords[3], barCoords[4]);
                        obj.context.fillRect(barCoords[1], barCoords[2], barCoords[3], barCoords[4]);

                        obj.context.stroke();
                        obj.context.fill();

                        RGraph.Tooltip(canvas, text, e.pageX, e.pageY, barCoords[5]);
                    }
                } else {
                    canvas.style.cursor = 'default';
                }
            }
            RGraph.AddEventListener(this.id, 'mousemove', canvas_onmousemove);
            this.canvas.addEventListener('mousemove', canvas_onmousemove, false);


            /**
            * Install the onclick event handler for the tooltips
            */
            if (this.Get('chart.tooltips.event') == 'onclick') {

                canvas_onclick = function (e)
                {
                    var e = RGraph.FixEventObject(e);

                    // If the button pressed isn't the left, we're not interested
                    if (e.button != 0) return;

                    e = RGraph.FixEventObject(e);

                    var canvas    = document.getElementById(this.id);
                    var obj       = canvas.__object__;
                    var barCoords = obj.getBar(e);

                    /**
                    * Redraw the graph first, in effect resetting the graph to as it was when it was first drawn
                    * This "deselects" any already selected bar
                    */
                    RGraph.Redraw();

                    /**
                    * Loop through the bars determining if the mouse is over a bar
                    */
                    if (barCoords) {

                        /**
                        * Get the tooltip text
                        */
                        if (typeof(obj.Get('chart.tooltips')) == 'function') {
                            var text = String(obj.Get('chart.tooltips')(barCoords[5]));

                        } else if (typeof(obj.Get('chart.tooltips')) == 'object' && typeof(obj.Get('chart.tooltips')[barCoords[5]]) == 'function') {
                            var text = String(obj.Get('chart.tooltips')[barCoords[5]](barCoords[5]));

                        } else if (typeof(obj.Get('chart.tooltips')) == 'object') {
                            var text = String(obj.Get('chart.tooltips')[barCoords[5]]);

                        } else {
                            var text = null;
                        }

                        /**
                        * Show a tooltip if it's defined
                        */
                        if (text && text != 'undefined') {

                            // [TODO] Allow customisation of the highlight colors
                            obj.context.beginPath();
                            obj.context.strokeStyle = 'black';
                            obj.context.fillStyle   = 'rgba(255,255,255,0.5)';
                            obj.context.strokeRect(barCoords[1], barCoords[2], barCoords[3], barCoords[4]);
                            obj.context.fillRect(barCoords[1], barCoords[2], barCoords[3], barCoords[4]);

                            obj.context.stroke();
                            obj.context.fill();

                            RGraph.Tooltip(canvas, text, e.pageX, e.pageY, barCoords[5]);
                        }
                    }

                    /**
                    * Stop the event bubbling
                    */
                    e.stopPropagation();
                }
                RGraph.AddEventListener(this.id, 'click', canvas_onclick);
                this.canvas.addEventListener('click', canvas_onclick, false);
            }


            // This resets the bar graph
            // 8th August 2010 : Is this redundant
            //if (typeof(obj) != 'undefined' && obj == RGraph.Registry.Get('chart.tooltip')) {
            //    obj.style.display = 'none';
            //    RGraph.Registry.Set('chart.tooltip', null)
            //}
        }
    }

    /**
    * Draws the labels for the graph
    */
    RGraph.Bar.prototype.DrawLabels = function ()
    {
        var context    = this.context;
        var gutter     = this.gutter;
        var text_angle = this.Get('chart.text.angle');
        var text_size  = this.Get('chart.text.size');
        var labels     = this.Get('chart.labels');


        // Draw the Y axis labels:
        if (this.Get('chart.ylabels')) {
            this.Drawlabels_center();
            this.Drawlabels_bottom();
        }

        /**
        * The X axis labels
        */
        if (typeof(labels) == 'object' && labels) {

            var yOffset = 13 + Number(this.Get('chart.xlabels.offset'));

            /**
            * Text angle
            */
            var angle  = 0;
            var halign = 'center';

            if (text_angle > 0) {
                angle  = -1 * text_angle;
                halign   = 'right';
                yOffset -= 5;
            }

            // Draw the X axis labels
            context.fillStyle = this.Get('chart.text.color');

            // How wide is each bar
            var barWidth = (this.canvas.width - (2 * gutter) ) / labels.length;

            // Reset the xTickGap
            xTickGap = (this.canvas.width - (2 * gutter)) / labels.length

            // Draw the X tickmarks
            var i=0;
            var font = this.Get('chart.text.font');

            for (x=gutter + (xTickGap / 2); x<=this.canvas.width - gutter; x+=xTickGap) {
                RGraph.Text(context, font,
                                      text_size,
                                      x + (this.Get('chart.text.angle') == 90 ? 0: 0),
                                      (this.canvas.height - gutter) + yOffset,
                                      String(labels[i++]),
                                      (this.Get('chart.text.angle') == 90 ? 'center' : null),
                                      halign,
                                      null,
                                      angle);
            }
        }
    }

    /**
    * Draws the X axis in the middle
    */
    RGraph.Bar.prototype.Drawlabels_center = function ()
    {
        var font       = this.Get('chart.text.font');
        var numYLabels = this.Get('chart.ylabels.count');

        this.context.fillStyle = this.Get('chart.text.color');

        if (this.Get('chart.xaxispos') == 'center') {

            /**
            * Draw the top labels
            */
            var interval   = (this.grapharea * (1/10) );
            var text_size  = this.Get('chart.text.size');
            var gutter     = this.gutter;
            var units_pre  = this.Get('chart.units.pre');
            var units_post = this.Get('chart.units.post');
            var context = this.context;
            var align   = '';
            var xpos    = 0;
            var boxed   = false;

            this.context.fillStyle = this.Get('chart.text.color');
            this.context.strokeStyle = 'black';

            if (this.Get('chart.ylabels.inside') == true) {
                var xpos  = this.Get('chart.yaxispos') == 'left' ? gutter + 5 : this.canvas.width - gutter - 5;
                var align = this.Get('chart.yaxispos') == 'left' ? 'left' : 'right';
                var boxed = true;
            } else {
                var xpos  = this.Get('chart.yaxispos') == 'left' ? gutter - 5 : this.canvas.width - gutter + 5;
                var align = this.Get('chart.yaxispos') == 'left' ? 'right' : 'left';
                var boxed = false;
            }












            /**
            * Draw specific Y labels here so that the local variables can be reused
            */
            if (typeof(this.Get('chart.ylabels.specific')) == 'object') {

                var labels = this.Get('chart.ylabels.specific');
                var grapharea = this.canvas.height - (2 * gutter);

                // Draw the top halves labels
                for (var i=0; i<labels.length; ++i) {
                    var y = gutter + (grapharea * (i / (labels.length * 2) ));

                    RGraph.Text(context, font, text_size, xpos, y, labels[i], 'center', align, boxed);
                }

                // Draw the bottom halves labels
                for (var i=labels.length-1; i>=0; --i) {
                    var y = gutter + (grapharea * ( (i+1) / (labels.length * 2) )) + (grapharea / 2);

                    RGraph.Text(context, font, text_size, xpos, y, labels[labels.length - i - 1], 'center', align, boxed);
                }

                return;
            }












            if (numYLabels == 3 || numYLabels == 5) {
                RGraph.Text(context, font, text_size, xpos, gutter + this.halfTextHeight, RGraph.number_format(this, this.scale[4], units_pre, units_post), null, align, boxed);

                if (numYLabels == 5) {
                    RGraph.Text(context, font, text_size, xpos, (1*interval) + gutter + this.halfTextHeight, RGraph.number_format(this, this.scale[3], units_pre, units_post), null, align, boxed);
                    RGraph.Text(context, font, text_size, xpos, (3*interval) + gutter + this.halfTextHeight, RGraph.number_format(this, this.scale[1], units_pre, units_post), null, align, boxed);
                }

                if (numYLabels == 3 || numYLabels == 5) {
                    RGraph.Text(context, font, text_size, xpos, (4*interval) + gutter + this.halfTextHeight, RGraph.number_format(this, this.scale[0], units_pre, units_post), null, align, boxed);
                    RGraph.Text(context, font, text_size, xpos, (2*interval) + gutter + this.halfTextHeight, RGraph.number_format(this, this.scale[2], units_pre, units_post), null, align, boxed);
                }
            } else if (numYLabels == 10) {
                // 10Y labels
                interval = (this.grapharea / numYLabels) / 2;

                for (var i=0; i<numYLabels; ++i) {
                    RGraph.Text(context, font, text_size, xpos,gutter + ((this.grapharea / (numYLabels * 2)) * i),RGraph.number_format(this, ((this.scale[4] / numYLabels) * (numYLabels - i)).toFixed((this.Get('chart.scale.decimals'))), units_pre, units_post), 'center', align, boxed);
                }
            }
            ///////////////////////////////////////////////////////////////////////////////////

            /**
            * Draw the bottom (X axis) labels
            */
            var interval = (this.grapharea) / 10;

            if (numYLabels == 3 || numYLabels == 5) {
                if (numYLabels == 3 || numYLabels == 5) {
                    RGraph.Text(context, font, text_size, xpos, (this.grapharea + gutter + this.halfTextHeight) - (4 * interval), '-' + RGraph.number_format(this, this.scale[0], units_pre, units_post), null, align, boxed);
                    RGraph.Text(context, font, text_size, xpos, (this.grapharea + gutter + this.halfTextHeight) - (2 * interval), '-' + RGraph.number_format(this, this.scale[2], units_pre, units_post), null, align, boxed);
                }

                if (numYLabels == 5) {
                    RGraph.Text(context, font, text_size, xpos, (this.grapharea + gutter + this.halfTextHeight) - (3 * interval), '-' + RGraph.number_format(this, this.scale[1], units_pre, units_post), null, align, boxed);
                    RGraph.Text(context, font, text_size, xpos, (this.grapharea + gutter + this.halfTextHeight) - interval, '-' + RGraph.number_format(this, this.scale[3], units_pre, units_post), null, align, boxed);
                }

                RGraph.Text(context, font, text_size, xpos,  this.grapharea + gutter + this.halfTextHeight, '-' + RGraph.number_format(this, this.scale[4], units_pre, units_post), null, align, boxed);

            } else if (numYLabels == 10) {

                // Arbitrary number of Y labels
                interval = (this.grapharea / numYLabels) / 2;

                for (var i=0; i<numYLabels; ++i) {
                    RGraph.Text(context, font, text_size, xpos,this.Get('chart.gutter') + (this.grapharea / 2) + ((this.grapharea / (numYLabels * 2)) * i) + (this.grapharea / (numYLabels * 2)),RGraph.number_format(this, ((this.scale[4] / numYLabels) * (i+1)).toFixed((this.Get('chart.scale.decimals'))), '-' + units_pre, units_post),'center', align, boxed);
                }
            }



        }
    }

    /**
    * Draws the X axdis at the bottom (the default)
    */
    RGraph.Bar.prototype.Drawlabels_bottom = function ()
    {
        this.context.beginPath();
        this.context.fillStyle = this.Get('chart.text.color');
        this.context.strokeStyle = 'black';

        if (this.Get('chart.xaxispos') != 'center') {

            var interval   = (this.grapharea * (1/5) );
            var text_size  = this.Get('chart.text.size');
            var units_pre  = this.Get('chart.units.pre');
            var units_post = this.Get('chart.units.post');
            var gutter     = this.gutter;
            var context    = this.context;
            var align      = this.Get('chart.yaxispos') == 'left' ? 'right' : 'left';
            var font       = this.Get('chart.text.font');
            var numYLabels = this.Get('chart.ylabels.count');

            if (this.Get('chart.ylabels.inside') == true) {
                var xpos  = this.Get('chart.yaxispos') == 'left' ? gutter + 5 : this.canvas.width - gutter - 5;
                var align = this.Get('chart.yaxispos') == 'left' ? 'left' : 'right';
                var boxed = true;
            } else {
                var xpos  = this.Get('chart.yaxispos') == 'left' ? gutter - 5 : this.canvas.width - gutter + 5;
                var boxed = false;
            }

            /**
            * Draw specific Y labels here so that the local variables can be reused
            */
            if (typeof(this.Get('chart.ylabels.specific')) == 'object') {

                var labels = this.Get('chart.ylabels.specific');
                var grapharea = this.canvas.height - (2 * gutter);

                for (var i=0; i<labels.length; ++i) {
                    var y = gutter + (grapharea * (i / labels.length));

                    RGraph.Text(context, font, text_size, xpos, y, labels[i], 'center', align, boxed);
                }

                return;
            }

            // 1 label
            if (numYLabels == 3 || numYLabels == 5) {
                RGraph.Text(context, font, text_size, xpos, gutter + this.halfTextHeight, RGraph.number_format(this, this.scale[4], units_pre, units_post), null, align, boxed);

                // 5 labels
                if (numYLabels == 5) {
                    RGraph.Text(context, font, text_size, xpos, (1*interval) + gutter + this.halfTextHeight, RGraph.number_format(this, this.scale[3], units_pre, units_post), null, align, boxed);
                    RGraph.Text(context, font, text_size, xpos, (3*interval) + gutter + this.halfTextHeight, RGraph.number_format(this, this.scale[1], units_pre, units_post), null, align, boxed);

                }

                // 3 labels
                if (numYLabels == 3 || numYLabels == 5) {
                    RGraph.Text(context, font, text_size, xpos, (2*interval) + gutter + this.halfTextHeight, RGraph.number_format(this, this.scale[2], units_pre, units_post), null, align, boxed);
                    RGraph.Text(context, font, text_size, xpos, (4*interval) + gutter + this.halfTextHeight, RGraph.number_format(this, this.scale[0], units_pre, units_post), null, align, boxed);
                }
            }

            // 10 Y labels
            if (numYLabels == 10) {

                interval   = (this.grapharea / numYLabels );

                for (var i=0; i<numYLabels; ++i) {
                    RGraph.Text(context, font, text_size, xpos, this.Get('chart.gutter') + ((this.grapharea / numYLabels) * i), RGraph.number_format(this,((this.scale[4] / numYLabels) * (numYLabels - i)).toFixed((this.Get('chart.scale.decimals'))), units_pre, units_post), 'center', align, boxed);
                }
            }
        }

        this.context.fill();
        this.context.stroke();
    }


    /**
    * This function is used by MSIE only to manually draw the shadow
    *
    * @param array coords The coords for the bar
    */
    RGraph.Bar.prototype.DrawIEShadow = function (coords)
    {
        var prevFillStyle = this.context.fillStyle;
        var offsetx       = this.Get('chart.shadow.offsetx');
        var offsety       = this.Get('chart.shadow.offsety');

        this.context.lineWidth = this.Get('chart.linewidth');
        this.context.fillStyle = this.Get('chart.shadow.color');
        this.context.beginPath();

        // Draw shadow here
        this.context.fillRect(coords[0] + offsetx, coords[1] + offsety, coords[2], coords[3]);

        this.context.fill();

        // Change the fillstyle back to what it was
        this.context.fillStyle = prevFillStyle;
    }


    /**
    * Not used by the class during creating the graph, but is used by event handlers
    * to get the coordinates (if any) of the selected bar
    */
    RGraph.Bar.prototype.getBar = function (e)
    {
        var canvas      = e.target;
        var obj         = e.target.__object__;
        var mouseCoords = RGraph.getMouseXY(e);

        /**
        * Loop through the bars determining if the mouse is over a bar
        */
        for (var i=0; i<obj.coords.length; i++) {

            var mouseX = mouseCoords[0];
            var mouseY = mouseCoords[1];

            var left   = obj.coords[i][0];
            var top    = obj.coords[i][1];
            var width  = obj.coords[i][2];
            var height = obj.coords[i][3];

            if (   mouseX >= (left + obj.Get('chart.tooltips.coords.adjust')[0])
                && mouseX <= (left + width+ obj.Get('chart.tooltips.coords.adjust')[0])
                && mouseY >= (top + obj.Get('chart.tooltips.coords.adjust')[1])
                && mouseY <= (top + height + obj.Get('chart.tooltips.coords.adjust')[1]) ) {

                return [obj, left, top, width, height, i];
            }
        }

        return null;
    }
