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

    /**
    * Initialise the various objects
    */
    if (typeof(RGraph) == 'undefined') RGraph = {isRGraph:true,type:'common'};


    RGraph.Registry       = {};
    RGraph.Registry.store = [];
    RGraph.Registry.store['chart.event.handlers'] = [];
    RGraph.background     = {};
    RGraph.objects        = [];
    RGraph.Resizing       = {};
    RGraph.events         = [];



    /**
    * Returns five values which are used as a nice scale
    *
    * @param  max int    The maximum value of the graph
    * @param  obj object The graph object
    * @return     array   An appropriate scale
    */
    RGraph.getScale = function (max, obj)
    {
        /**
        * Special case for 0
        */
        if (max == 0) {
            return ['0.2', '0.4', '0.6', '0.8', '1.0'];
        }

        var original_max = max;

        /**
        * Manually do decimals
        */
        if (max <= 1) {
            if (max > 0.5) {
                return [0.2,0.4,0.6,0.8, Number(1).toFixed(1)];

            } else if (max >= 0.1) {
                return obj.Get('chart.scale.round') ? [0.2,0.4,0.6,0.8,1] : [0.1,0.2,0.3,0.4,0.5];

            } else {

                var tmp = max;
                var exp = 0;

                while (tmp < 1.01) {
                    exp += 1;
                    tmp *= 10;
                }

                var ret = ['2e-' + exp, '4e-' + exp, '6e-' + exp, '8e-' + exp, '10e-' + exp];


                if (max <= ('5e-' + exp)) {
                    ret = ['1e-' + exp, '2e-' + exp, '3e-' + exp, '4e-' + exp, '5e-' + exp];
                }

                return ret;
            }
        }

        // Take off any decimals
        if (String(max).indexOf('.') > 0) {
            max = String(max).replace(/\.\d+$/, '');
        }

        var interval = Math.pow(10, Number(String(Number(max)).length - 1));
        var topValue = interval;

        while (topValue < max) {
            topValue += (interval / 2);
        }

        // Handles cases where the max is (for example) 50.5
        if (Number(original_max) > Number(topValue)) {
            topValue += (interval / 2);
        }

        // Custom if the max is greater than 5 and less than 10
        if (max < 10) {
            topValue = (Number(original_max) <= 5 ? 5 : 10);
        }

        /**
        * Added 02/11/2010 to create "nicer" scales
        */
        if (obj && typeof(obj.Get('chart.scale.round')) == 'boolean' && obj.Get('chart.scale.round')) {
            topValue = 10 * interval;
        }

        return [topValue * 0.2, topValue * 0.4, topValue * 0.6, topValue * 0.8, topValue];
    }


    /**
    * Returns the maximum numeric value which is in an array
    *
    * @param  array arr The array (can also be a number, in which case it's returned as-is)
    * @param  int       Whether to ignore signs (ie negative/positive)
    * @return int       The maximum value in the array
    */
    RGraph.array_max = function (arr)
    {
        var max = null;

        if (typeof(arr) == 'number') {
            return arr;
        }

        for (var i=0; i<arr.length; ++i) {
            if (typeof(arr[i]) == 'number') {

                var val = arguments[1] ? Math.abs(arr[i]) : arr[i];

                if (typeof(max) == 'number') {
                    max = Math.max(max, val);
                } else {
                    max = val;
                }
            }
        }

        return max;
    }


    /**
    * Returns the maximum value which is in an array
    *
    * @param  array arr The array
    * @param  int   len The length to pad the array to
    * @param  mixed     The value to use to pad the array (optional)
    */
    RGraph.array_pad = function (arr, len)
    {
        if (arr.length < len) {
            var val = arguments[2] ? arguments[2] : null;

            for (var i=arr.length; i<len; ++i) {
                arr[i] = val;
            }
        }

        return arr;
    }


    /**
    * An array sum function
    *
    * @param  array arr The  array to calculate the total of
    * @return int       The summed total of the arrays elements
    */
    RGraph.array_sum = function (arr)
    {
        // Allow integers
        if (typeof(arr) == 'number') {
            return arr;
        }

        var i, sum;
        var len = arr.length;

        for(i=0,sum=0;i<len;sum+=arr[i++]);
        return sum;
    }



    /**
    * A simple is_array() function
    *
    * @param  mixed obj The object you want to check
    * @return bool      Whether the object is an array or not
    */
    RGraph.is_array = function (obj)
    {
        return obj != null && obj.constructor.toString().indexOf('Array') != -1;
    }


    /**
    * Converts degrees to radians
    *
    * @param  int degrees The number of degrees
    * @return float       The number of radians
    */
    RGraph.degrees2Radians = function (degrees)
    {
        return degrees * (Math.PI / 180);
    }


    /**
    * This function draws an angled line. The angle is cosidered to be clockwise
    *
    * @param obj ctxt   The context object
    * @param int x      The X position
    * @param int y      The Y position
    * @param int angle  The angle in RADIANS
    * @param int length The length of the line
    */
    RGraph.lineByAngle = function (context, x, y, angle, length)
    {
        context.arc(x, y, length, angle, angle, false);
        context.lineTo(x, y);
        context.arc(x, y, length, angle, angle, false);
    }


    /**
    * This is a useful function which is basically a shortcut for drawing left, right, top and bottom alligned text.
    *
    * @param object context The context
    * @param string font    The font
    * @param int    size    The size of the text
    * @param int    x       The X coordinate
    * @param int    y       The Y coordinate
    * @param string text    The text to draw
    * @parm  string         The vertical alignment. Can be null. "center" gives center aligned  text, "top" gives top aligned text.
    *                       Anything else produces bottom aligned text. Default is bottom.
    * @param  string        The horizontal alignment. Can be null. "center" gives center aligned  text, "right" gives right aligned text.
    *                       Anything else produces left aligned text. Default is left.
    * @param  bool          Whether to show a bounding box around the text. Defaults not to
    * @param int            The angle that the text should be rotate at (IN DEGREES)
    * @param string         Background color for the text
    * @param bool           Whether the text is bold or not
    * @param bool           Whether the bounding box has a placement indicator
    */
    RGraph.Text = function (context, font, size, x, y, text)
    {
        /**
        * This calls the text function recursively to accommodate multi-line text
        */
        if (typeof(text) == 'string' && text.match(/\r\n/)) {

            var arr = text.split('\r\n');

            text = arr[0];
            arr = RGraph.array_shift(arr);

            var nextline = arr.join('\r\n')

            RGraph.Text(context, font, size, arguments[9] == -90 ? (x + (size * 1.5)) : x, y + (size * 1.5), nextline, arguments[6] ? arguments[6] : null, 'center', arguments[8], arguments[9], arguments[10], arguments[11], arguments[12]);
        }


        // Accommodate MSIE
        if (RGraph.isIE8()) {
            y += 2;
        }


        context.font = (arguments[11] ? 'Bold ': '') + size + 'pt ' + font;

        var i;
        var origX = x;
        var origY = y;
        var originalFillStyle = context.fillStyle;
        var originalLineWidth = context.lineWidth;

        // Need these now the angle can be specified, ie defaults for the former two args
        if (typeof(arguments[6]) == null) arguments[6]  = 'bottom'; // Vertical alignment. Default to bottom/baseline
        if (typeof(arguments[7]) == null) arguments[7]  = 'left';   // Horizontal alignment. Default to left
        if (typeof(arguments[8]) == null) arguments[8]  = null;     // Show a bounding box. Useful for positioning during development. Defaults to false
        if (typeof(arguments[9]) == null) arguments[9]  = 0;        // Angle (IN DEGREES) that the text should be drawn at. 0 is middle right, and it goes clockwise
        if (typeof(arguments[12]) == null) arguments[12] = true;    // Whether the bounding box has the placement indicator

        // The alignment is recorded here for purposes of Opera compatibility
        if (navigator.userAgent.indexOf('Opera') != -1) {
            context.canvas.__rgraph_valign__ = arguments[6];
            context.canvas.__rgraph_halign__ = arguments[7];
        }

        // First, translate to x/y coords
        context.save();

            context.canvas.__rgraph_originalx__ = x;
            context.canvas.__rgraph_originaly__ = y;

            context.translate(x, y);
            x = 0;
            y = 0;

            // Rotate the canvas if need be
            if (arguments[9]) {
                context.rotate(arguments[9] / 57.3);
            }

            // Vertical alignment - defaults to bottom
            if (arguments[6]) {
                var vAlign = arguments[6];

                if (vAlign == 'center') {
                    context.translate(0, size / 2);
                } else if (vAlign == 'top') {
                    context.translate(0, size);
                }
            }


            // Hoeizontal alignment - defaults to left
            if (arguments[7]) {
                var hAlign = arguments[7];
                var width  = context.measureText(text).width;

                if (hAlign) {
                    if (hAlign == 'center') {
                        context.translate(-1 * (width / 2), 0)
                    } else if (hAlign == 'right') {
                        context.translate(-1 * width, 0)
                    }
                }
            }


            context.fillStyle = originalFillStyle;

            /**
            * Draw a bounding box if requested
            */
            context.save();
                 context.fillText(text,0,0);
                 context.lineWidth = 0.5;

                if (arguments[8]) {

                    var width = context.measureText(text).width;
                    var ieOffset = RGraph.isIE8() ? 2 : 0;

                    context.translate(x, y);
                    context.strokeRect(0 - 3, 0 - 3 - size - ieOffset, width + 6, 0 + size + 6);

                    /**
                    * If requested, draw a background for the text
                    */
                    if (arguments[10]) {

                        var offset = 3;
                        var ieOffset = RGraph.isIE8() ? 2 : 0;
                        var width = context.measureText(text).width

                        //context.strokeStyle = 'gray';
                        context.fillStyle = arguments[10];
                        context.fillRect(x - offset, y - size - offset - ieOffset, width + (2 * offset), size + (2 * offset));
                        //context.strokeRect(x - offset, y - size - offset - ieOffset, width + (2 * offset), size + (2 * offset));
                    }

                    /**
                    * Do the actual drawing of the text
                    */
                    context.fillStyle = originalFillStyle;
                    context.fillText(text,0,0);

                    if (arguments[12]) {
                        context.fillRect(
                            arguments[7] == 'left' ? 0 : (arguments[7] == 'center' ? width / 2 : width ) - 2,
                            arguments[6] == 'bottom' ? 0 : (arguments[6] == 'center' ? (0 - size) / 2 : 0 - size) - 2,
                            4,
                            4
                        );
                    }
                }
            context.restore();

            // Reset the lineWidth
            context.lineWidth = originalLineWidth;

        context.restore();
    }


    /**
    * Clears the canvas by setting the width. You can specify a colour if you wish.
    *
    * @param object canvas The canvas to clear
    */
    RGraph.Clear = function (canvas)
    {
        var context = canvas.getContext('2d');
        var color   = arguments[1];

        if (RGraph.isIE8() && !color) {
            color = 'white';
        }

        /**
        * Can now clear the canvas back to fully transparent
        */
        if (!color || (color && color == 'transparent')) {

            context.fillStyle = 'rgba(0,0,0,0)';
            context.globalCompositeOperation = 'source-in';
            context = canvas.getContext('2d');
            context.beginPath();
            context.fillRect(-1000,-1000,canvas.width + 2000,canvas.height + 2000);
            context.fill();

            // Reset the globalCompositeOperation
            context.globalCompositeOperation = 'source-over';

        } else {

            context.fillStyle = color;
            context = canvas.getContext('2d');
            context.beginPath();

            if (RGraph.isIE8()) {
                context.fillRect(0,0,canvas.width,canvas.height);
            } else {
                context.fillRect(-1000,-1000,canvas.width + 2000,canvas.height + 2000);
            }

            context.fill();
        }

        // Don't do this as it also clears any translation that may have occurred
        //canvas.width = canvas.width;

        if (RGraph.ClearAnnotations) {
            RGraph.ClearAnnotations(canvas.id);
        }

        RGraph.FireCustomEvent(canvas.__object__, 'onclear');
    }


    /**
    * Draws the title of the graph
    *
    * @param object  canvas The canvas object
    * @param string  text   The title to write
    * @param integer gutter The size of the gutter
    * @param integer        The center X point (optional - if not given it will be generated from the canvas width)
    * @param integer        Size of the text. If not given it will be 14
    */
    RGraph.DrawTitle = function (canvas, text, gutterTop)
    {
        var obj          = canvas.__object__;
        var context      = canvas.getContext('2d');
        var gutterLeft   = obj.Get('chart.gutter.left');
        var gutterRight  = obj.Get('chart.gutter.right');
        var gutterBottom = obj.Get('chart.gutter.bottom');
        var size         = arguments[4] ? arguments[4] : 12;
        var centerx      = (arguments[3] ? arguments[3] : ((RGraph.GetWidth(obj) - gutterLeft - gutterRight) / 2) + gutterLeft);
        var keypos       = obj.Get('chart.key.position');
        var vpos         = obj.Get('chart.title.vpos');
        var hpos         = obj.Get('chart.title.hpos');
        var bgcolor      = obj.Get('chart.title.background');

        // Account for 3D effect by faking the key position
        if (obj.type == 'bar' && obj.Get('chart.variant') == '3d') {
            keypos = 'gutter';
        }

        context.beginPath();
        context.fillStyle = obj.Get('chart.text.color') ? obj.Get('chart.text.color') : 'black';

        /**
        * Vertically center the text if the key is not present
        */
        if (keypos && keypos != 'gutter') {
            var vCenter = 'center';

        } else if (!keypos) {
            var vCenter = 'center';

        } else {
            var vCenter = 'bottom';
        }

        // if chart.title.vpos does not equal 0.5, use that
        if (typeof(obj.Get('chart.title.vpos')) == 'number') {
            vpos = obj.Get('chart.title.vpos') * gutterTop;

            if (obj.Get('chart.xaxispos') == 'top') {
                vpos = obj.Get('chart.title.vpos') * gutterBottom + gutterTop + (obj.canvas.height - gutterTop - gutterBottom);
            }
        } else {
            vpos = gutterTop - size - 5;

            if (obj.Get('chart.xaxispos') == 'top') {
                vpos = obj.canvas.height  - gutterBottom + size + 5;
            }
        }

        // if chart.title.hpos is a number, use that. It's multiplied with the (entire) canvas width
        if (typeof(hpos) == 'number') {
            centerx = hpos * canvas.width;
        }

        // Set the colour
        if (typeof(obj.Get('chart.title.color') != null)) {
            var oldColor = context.fillStyle
            var newColor = obj.Get('chart.title.color')
            context.fillStyle = newColor ? newColor : 'black';
        }

        /**
        * Default font is Verdana
        */
        var font = obj.Get('chart.text.font');

        /**
        * Draw the title itself
        */
        RGraph.Text(context, font, size, centerx, vpos, text, vCenter, 'center', bgcolor != null, null, bgcolor, true);

        // Reset the fill colour
        context.fillStyle = oldColor;
    }


    /**
    * This function returns the mouse position in relation to the canvas
    *
    * @param object e The event object.
    */
    RGraph.getMouseXY = function (e)
    {
        var obj = (RGraph.isIE8() ? event.srcElement : e.target);
        var x;
        var y;

        if (RGraph.isIE8()) e = event;

        // Browser with offsetX and offsetY
        if (typeof(e.offsetX) == 'number' && typeof(e.offsetY) == 'number') {
            x = e.offsetX;
            y = e.offsetY;

        // FF and other
        } else {
            x = 0;
            y = 0;

            while (obj != document.body && obj) {
                x += obj.offsetLeft;
                y += obj.offsetTop;

                obj = obj.offsetParent;
            }

            x = e.pageX - x;
            y = e.pageY - y;
        }

        return [x, y];
    }


    /**
    * This function returns a two element array of the canvas x/y position in
    * relation to the page
    *
    * @param object canvas
    */
    RGraph.getCanvasXY = function (canvas)
    {
        var x   = 0;
        var y   = 0;
        var obj = canvas;

        do {

            x += obj.offsetLeft;
            y += obj.offsetTop;

            obj = obj.offsetParent;

        } while (obj && obj.tagName.toLowerCase() != 'body');

        return [x, y];
    }


    /**
    * Registers a graph object (used when the canvas is redrawn)
    *
    * @param object obj The object to be registered
    */
    RGraph.Register = function (obj)
    {
        var key = obj.id + '_' + obj.type;

        RGraph.objects[key] = obj;
    }


    /**
    * Causes all registered objects to be redrawn
    *
    * @param string   An optional string indicating which canvas is not to be redrawn
    * @param string An optional color to use to clear the canvas
    */
    RGraph.Redraw = function ()
    {
        for (i in RGraph.objects) {
            // TODO FIXME Maybe include more intense checking for whether the object is an RGraph object, eg obj.isRGraph == true ...?
            if (
                   typeof(i) == 'string'
                && typeof(RGraph.objects[i]) == 'object'
                && typeof(RGraph.objects[i].type) == 'string'
                && RGraph.objects[i].isRGraph)  {

                if (!arguments[0] || arguments[0] != RGraph.objects[i].id) {
                    RGraph.Clear(RGraph.objects[i].canvas, arguments[1] ? arguments[1] : null);
                    RGraph.objects[i].Draw();
                }
            }
        }
    }


    /**
    * Loosly mimicks the PHP function print_r();
    */
    RGraph.pr = function (obj)
    {
        var str = '';
        var indent = (arguments[2] ? arguments[2] : '');

        switch (typeof(obj)) {
            case 'number':
                if (indent == '') {
                    str+= 'Number: '
                }
                str += String(obj);
                break;

            case 'string':
                if (indent == '') {
                    str+= 'String (' + obj.length + '):'
                }
                str += '"' + String(obj) + '"';
                break;

            case 'object':
                // In case of null
                if (obj == null) {
                    str += 'null';
                    break;
                }

                str += 'Object\n' + indent + '(\n';

                for (var i=0; i<obj.length; ++i) {
                    str += indent + ' ' + i + ' => ' + RGraph.pr(obj[i], true, indent + '    ') + '\n';
                }

                var str = str + indent + ')';
                break;

            case 'function':
                str += obj;
                break;

            case 'boolean':
                str += 'Boolean: ' + (obj ? 'true' : 'false');
                break;
        }

        /**
        * Finished, now either return if we're in a recursed call, or alert()
        * if we're not.
        */
        if (arguments[1]) {
            return str;
        } else {
            alert(str);
        }
    }


    /**
    * The RGraph registry Set() function
    *
    * @param  string name  The name of the key
    * @param  mixed  value The value to set
    * @return mixed        Returns the same value as you pass it
    */
    RGraph.Registry.Set = function (name, value)
    {
        // Store the setting
        RGraph.Registry.store[name] = value;

        // Don't really need to do this, but ho-hum
        return value;
    }


    /**
    * The RGraph registry Get() function
    *
    * @param  string name The name of the particular setting to fetch
    * @return mixed       The value if exists, null otherwise
    */
    RGraph.Registry.Get = function (name)
    {
        //return RGraph.Registry.store[name] == null ? null : RGraph.Registry.store[name];
        return RGraph.Registry.store[name];
    }


    /**
    * This function draws the background for the bar chart, line chart and scatter chart.
    *
    * @param  object obj The graph object
    */
    RGraph.background.Draw = function (obj)
    {
        var canvas       = obj.canvas;
        var context      = obj.context;
        var height       = 0;
        var gutterLeft   = obj.Get('chart.gutter.left');
        var gutterRight  = obj.Get('chart.gutter.right');
        var gutterTop    = obj.Get('chart.gutter.top');
        var gutterBottom = obj.Get('chart.gutter.bottom');
        var variant      = obj.Get('chart.variant');

        context.fillStyle = obj.Get('chart.text.color');

        // If it's a bar and 3D variant, translate
        if (variant == '3d') {
            context.save();
            context.translate(10, -5);
        }

        // X axis title
        if (typeof(obj.Get('chart.title.xaxis')) == 'string' && obj.Get('chart.title.xaxis').length) {

            var size = obj.Get('chart.text.size');
            var font = obj.Get('chart.text.font');

            context.beginPath();
            RGraph.Text(context, font, size + 2, RGraph.GetWidth(obj) / 2, RGraph.GetHeight(obj) - (gutterBottom * obj.Get('chart.title.xaxis.pos')), obj.Get('chart.title.xaxis'), 'center', 'center', false, false, false, true);
            context.fill();
        }

        // Y axis title
        if (typeof(obj.Get('chart.title.yaxis')) == 'string' && obj.Get('chart.title.yaxis').length) {

            var size            = obj.Get('chart.text.size');
            var font            = obj.Get('chart.text.font');
            var angle           = 270;
            var yaxis_title_pos = obj.Get('chart.title.yaxis.pos');

            if (obj.Get('chart.title.yaxis.position') == 'right') {
                angle = 90;
                yaxis_title_pos = yaxis_title_pos * obj.Get('chart.gutter.right') + (obj.canvas.width - obj.Get('chart.gutter.right'));
            } else {
                yaxis_title_pos *= obj.Get('chart.gutter.left');
            }


            context.beginPath();
            RGraph.Text(context,
                        font,
                        size + 2,
                        yaxis_title_pos,
                        RGraph.GetHeight(obj) / 2,
                        obj.Get('chart.title.yaxis'),
                        'center',
                        'center',
                        false,
                        angle,
                        false,
                        true);
            context.fill();
        }

        obj.context.beginPath();

        // Draw the horizontal bars
        context.fillStyle = obj.Get('chart.background.barcolor1');
        height = (RGraph.GetHeight(obj) - gutterBottom);

        for (var i=gutterTop; i < height ; i+=80) {
            obj.context.fillRect(gutterLeft, i, RGraph.GetWidth(obj) - gutterLeft - gutterRight, Math.min(40, RGraph.GetHeight(obj) - gutterBottom - i) );
        }

            context.fillStyle = obj.Get('chart.background.barcolor2');
            height = (RGraph.GetHeight(obj) - gutterBottom);

            for (var i= (40 + gutterTop); i < height; i+=80) {
                obj.context.fillRect(gutterLeft, i, RGraph.GetWidth(obj) - gutterLeft - gutterRight, i + 40 > (RGraph.GetHeight(obj) - gutterBottom) ? RGraph.GetHeight(obj) - (gutterBottom + i) : 40);
            }

            context.stroke();


        // Draw the background grid
        if (obj.Get('chart.background.grid')) {

            // If autofit is specified, use the .numhlines and .numvlines along with the width to work
            // out the hsize and vsize
            if (obj.Get('chart.background.grid.autofit')) {

                /**
                * Align the grid to the tickmarks
                */
                if (obj.Get('chart.background.grid.autofit.align')) {

                    // Align the horizontal lines
                    obj.Set('chart.background.grid.autofit.numhlines', obj.Get('chart.ylabels.count'));

                    // Align the vertical lines for the line
                    if (obj.type == 'line') {
                        if (obj.Get('chart.labels') && obj.Get('chart.labels').length) {
                            obj.Set('chart.background.grid.autofit.numvlines', obj.Get('chart.labels').length - 1);
                        } else {
                            obj.Set('chart.background.grid.autofit.numvlines', obj.data[0].length - 1);
                        }

                    // Align the vertical lines for the bar
                    } else if (obj.type == 'bar' && obj.Get('chart.labels') && obj.Get('chart.labels').length) {
                        obj.Set('chart.background.grid.autofit.numvlines', obj.Get('chart.labels').length);
                    }
                }

                var vsize = ((RGraph.GetWidth(obj) - gutterLeft - gutterRight)) / obj.Get('chart.background.grid.autofit.numvlines');
                var hsize = (RGraph.GetHeight(obj) - gutterTop - gutterBottom) / obj.Get('chart.background.grid.autofit.numhlines');

                obj.Set('chart.background.grid.vsize', vsize);
                obj.Set('chart.background.grid.hsize', hsize);
            }

            context.beginPath();
            context.lineWidth   = obj.Get('chart.background.grid.width') ? obj.Get('chart.background.grid.width') : 1;
            context.strokeStyle = obj.Get('chart.background.grid.color');

            // Draw the horizontal lines
            if (obj.Get('chart.background.grid.hlines')) {
                height = (RGraph.GetHeight(obj) - gutterBottom)
                for (y=gutterTop; y<height; y+=obj.Get('chart.background.grid.hsize')) {
                    context.moveTo(gutterLeft, y);
                    context.lineTo(RGraph.GetWidth(obj) - gutterRight, y);
                }
            }

            if (obj.Get('chart.background.grid.vlines')) {
                // Draw the vertical lines
                var width = (RGraph.GetWidth(obj) - gutterRight)
                for (x=gutterLeft; x<=width; x+=obj.Get('chart.background.grid.vsize')) {
                    context.moveTo(x, gutterTop);
                    context.lineTo(x, RGraph.GetHeight(obj) - gutterBottom);
                }
            }

            if (obj.Get('chart.background.grid.border')) {
                // Make sure a rectangle, the same colour as the grid goes around the graph
                context.strokeStyle = obj.Get('chart.background.grid.color');
                context.strokeRect(gutterLeft, gutterTop, RGraph.GetWidth(obj) - gutterLeft - gutterRight, RGraph.GetHeight(obj) - gutterTop - gutterBottom);
            }
        }

        context.stroke();

        // If it's a bar and 3D variant, translate
        if (variant == '3d') {
            context.restore();
        }

        // Draw the title if one is set
        if ( typeof(obj.Get('chart.title')) == 'string') {

            if (obj.type == 'gantt') {
                gutterTop -= 10;
            }

            RGraph.DrawTitle(canvas,
                             obj.Get('chart.title'),
                             gutterTop,
                             null,
                             obj.Get('chart.text.size') + 2);
        }

        context.stroke();
    }


    /**
    * Returns the day number for a particular date. Eg 1st February would be 32
    *
    * @param   object obj A date object
    * @return  int        The day number of the given date
    */
    RGraph.GetDays = function (obj)
    {
        var year  = obj.getFullYear();
        var days  = obj.getDate();
        var month = obj.getMonth();

        if (month == 0) return days;
        if (month >= 1) days += 31;
        if (month >= 2) days += 28;

            // Leap years. Crude, but if this code is still being used
            // when it stops working, then you have my permission to shoot
            // me. Oh, you won't be able to - I'll be dead...
            if (year >= 2008 && year % 4 == 0) days += 1;

        if (month >= 3) days += 31;
        if (month >= 4) days += 30;
        if (month >= 5) days += 31;
        if (month >= 6) days += 30;
        if (month >= 7) days += 31;
        if (month >= 8) days += 31;
        if (month >= 9) days += 30;
        if (month >= 10) days += 31;
        if (month >= 11) days += 30;

        return days;
    }















    /**
    * Draws the graph key (used by various graphs)
    *
    * @param object obj The graph object
    * @param array  key An array of the texts to be listed in the key
    * @param colors An array of the colors to be used
    */
    RGraph.DrawKey = function (obj, key, colors)
    {
        var canvas  = obj.canvas;
        var context = obj.context;
        context.lineWidth = 1;

        context.beginPath();

        /**
        * Key positioned in the gutter
        */
        var keypos   = obj.Get('chart.key.position');
        var textsize = obj.Get('chart.text.size');

        /**
        * Change the older chart.key.vpos to chart.key.position.y
        */
        if (typeof(obj.Get('chart.key.vpos')) == 'number') {
            obj.Set('chart.key.position.y', obj.Get('chart.key.vpos') * this.Get('chart.gutter.top') );
        }

        /**
        * Account for null values in the key
        */
        var key_non_null    = [];
        var colors_non_null = [];
        for (var i=0; i<key.length; ++i) {
            if (key[i] != null) {
                colors_non_null.push(colors[i]);
                key_non_null.push(key[i]);
            }
        }

        key    = key_non_null;
        colors = colors_non_null;



        if (keypos && keypos == 'gutter') {

            RGraph.DrawKey_gutter(obj, key, colors);


        /**
        * In-graph style key
        */
        } else if (keypos && keypos == 'graph') {

            RGraph.DrawKey_graph(obj, key, colors);

        } else {
            alert('[COMMON] (' + obj.id + ') Unknown key position: ' + keypos);
        }
    }





    /**
    * This does the actual drawing of the key when it's in the graph
    *
    * @param object obj The graph object
    * @param array  key The key items to draw
    * @param array colors An aray of colors that the key will use
    */
    RGraph.DrawKey_graph = function (obj, key, colors)
    {
        var canvas      = obj.canvas;
        var context     = obj.context;
        var text_size   = typeof(obj.Get('chart.key.text.size')) == 'number' ? obj.Get('chart.key.text.size') : obj.Get('chart.text.size');
        var text_font   = obj.Get('chart.text.font');

        var gutterLeft   = obj.Get('chart.gutter.left');
        var gutterRight  = obj.Get('chart.gutter.right');
        var gutterTop    = obj.Get('chart.gutter.top');
        var gutterBottom = obj.Get('chart.gutter.bottom');

        var hpos        = obj.Get('chart.yaxispos') == 'right' ? gutterLeft + 10 : RGraph.GetWidth(obj) - gutterRight - 10;
        var vpos        = gutterTop + 10;
        var title       = obj.Get('chart.title');
        var blob_size   = text_size; // The blob of color
        var hmargin      = 8; // This is the size of the gaps between the blob of color and the text
        var vmargin      = 4; // This is the vertical margin of the key
        var fillstyle    = obj.Get('chart.key.background');
        var strokestyle  = '#333';
        var height       = 0;
        var width        = 0;


        obj.coordsKey = [];


        // Need to set this so that measuring the text works out OK
        context.font = text_size + 'pt ' + obj.Get('chart.text.font');

        // Work out the longest bit of text
        for (i=0; i<key.length; ++i) {
            width = Math.max(width, context.measureText(key[i]).width);
        }

        width += 5;
        width += blob_size;
        width += 5;
        width += 5;
        width += 5;

        /**
        * Now we know the width, we can move the key left more accurately
        */
        if (   obj.Get('chart.yaxispos') == 'left'
            || (obj.type == 'pie' && !obj.Get('chart.yaxispos'))
            || (obj.type == 'hbar' && !obj.Get('chart.yaxispos'))
            || (obj.type == 'hbar' && obj.Get('chart.yaxispos') == 'center')
            || (obj.type == 'rscatter' && !obj.Get('chart.yaxispos'))
            || (obj.type == 'radar' && !obj.Get('chart.yaxispos'))
            || (obj.type == 'rose' && !obj.Get('chart.yaxispos'))
            || (obj.type == 'funnel' && !obj.Get('chart.yaxispos'))
            || (obj.type == 'vprogress' && !obj.Get('chart.yaxispos'))
            || (obj.type == 'hprogress' && !obj.Get('chart.yaxispos'))
           ) {

            hpos -= width;
        }

        /**
        * Horizontal alignment
        */
        if (typeof(obj.Get('chart.key.halign')) == 'string') {
            if (obj.Get('chart.key.halign') == 'left') {
                hpos = gutterLeft + 10;
            } else if (obj.Get('chart.key.halign') == 'right') {
                hpos = RGraph.GetWidth(obj) - gutterRight  - width;
            }
        }

        /**
        * Specific location coordinates
        */
        if (typeof(obj.Get('chart.key.position.x')) == 'number') {
            hpos = obj.Get('chart.key.position.x');
        }

        if (typeof(obj.Get('chart.key.position.y')) == 'number') {
            vpos = obj.Get('chart.key.position.y');
        }


        // Stipulate the shadow for the key box
        if (obj.Get('chart.key.shadow')) {
            context.shadowColor   = obj.Get('chart.key.shadow.color');
            context.shadowBlur    = obj.Get('chart.key.shadow.blur');
            context.shadowOffsetX = obj.Get('chart.key.shadow.offsetx');
            context.shadowOffsetY = obj.Get('chart.key.shadow.offsety');
        }




        // Draw the box that the key resides in
        context.beginPath();
            context.fillStyle   = obj.Get('chart.key.background');
            context.strokeStyle = 'black';


        if (arguments[3] != false) {

            context.lineWidth = obj.Get('chart.key.linewidth') ? obj.Get('chart.key.linewidth') : 1;

            // The older square rectangled key
            if (obj.Get('chart.key.rounded') == true) {
                context.beginPath();
                    context.strokeStyle = strokestyle;
                    RGraph.strokedCurvyRect(context, hpos, vpos, width - 5, 5 + ( (text_size + 5) * RGraph.getKeyLength(key)),4);

                context.stroke();
                context.fill();

                RGraph.NoShadow(obj);

            } else {
                context.strokeRect(hpos, vpos, width - 5, 5 + ( (text_size + 5) * RGraph.getKeyLength(key)));
                context.fillRect(hpos, vpos, width - 5, 5 + ( (text_size + 5) * RGraph.getKeyLength(key)));
            }
        }

        RGraph.NoShadow(obj);

        context.beginPath();

            // Draw the labels given
            for (var i=key.length - 1; i>=0; i--) {

                var j = Number(i) + 1;

                // Draw the blob of color
                if (obj.Get('chart.key.color.shape') == 'circle') {
                    context.beginPath();
                        context.strokeStyle = 'rgba(0,0,0,0)';
                        context.fillStyle = colors[i];
                        context.arc(hpos + 5 + (blob_size / 2), vpos + (5 * j) + (text_size * j) - text_size + (blob_size / 2), blob_size / 2, 0, 6.26, 0);
                    context.fill();

                } else if (obj.Get('chart.key.color.shape') == 'line') {
                    context.beginPath();
                        context.strokeStyle = colors[i];
                        context.moveTo(hpos + 5, vpos + (5 * j) + (text_size * j) - text_size + (blob_size / 2));
                        context.lineTo(hpos + blob_size + 5, vpos + (5 * j) + (text_size * j) - text_size + (blob_size / 2));
                    context.stroke();

                } else {
                    context.fillStyle =  colors[i];
                    context.fillRect(hpos + 5, vpos + (5 * j) + (text_size * j) - text_size, text_size, text_size + 1);
                }

                context.beginPath();

                context.fillStyle = 'black';

                RGraph.Text(context,
                            text_font,
                            text_size,
                            hpos + blob_size + 5 + 5,
                            vpos + (5 * j) + (text_size * j),
                            key[i]);

                if (obj.Get('chart.key.interactive')) {

                    var px = hpos + 5;
                    var py = vpos + (5 * j) + (text_size * j) - text_size;
                    var pw = width - 5 - 5 - 5;
                    var ph = text_size;


                    obj.coordsKey.push([px, py, pw, ph]);
                }

            }
        context.fill();

        /**
        * Install the interactivity event handler
        */
        if (obj.Get('chart.key.interactive')) {

            RGraph.Register(obj);

            var key_mousemove = function (e)
            {
                var obj         = e.target.__object__;
                var canvas      = obj.canvas;
                var context     = obj.context;
                var mouseCoords = RGraph.getMouseXY(e);
                var mouseX      = mouseCoords[0];
                var mouseY      = mouseCoords[1];

                for (var i=0; i<obj.coordsKey.length; ++i) {

                    var px = obj.coordsKey[i][0];
                    var py = obj.coordsKey[i][1];
                    var pw = obj.coordsKey[i][2];
                    var ph = obj.coordsKey[i][3];

                    if (   mouseX > px && mouseX < (px + pw) && mouseY > py && mouseY < (py + ph) ) {

                        // Necessary?
                        //var index = obj.coordsKey.length - i - 1;

                        canvas.style.cursor = 'pointer';

                        return;
                    }

                    canvas.style.cursor = 'default';
                }
            }
            canvas.addEventListener('mousemove', key_mousemove, false);
            RGraph.AddEventListener(canvas.id, 'mousemove', key_mousemove);


            var key_click = function (e)
            {
                RGraph.Redraw();

                var obj         = e.target.__object__;
                var canvas      = obj.canvas;
                var context     = obj.context;
                var mouseCoords = RGraph.getMouseXY(e);
                var mouseX      = mouseCoords[0];
                var mouseY      = mouseCoords[1];

                RGraph.DrawKey(obj, obj.Get('chart.key'), obj.Get('chart.colors'));

                for (var i=0; i<obj.coordsKey.length; ++i) {

                    var px = obj.coordsKey[i][0];
                    var py = obj.coordsKey[i][1];
                    var pw = obj.coordsKey[i][2];
                    var ph = obj.coordsKey[i][3];

                    if (   mouseX > px && mouseX < (px + pw) && mouseY > py && mouseY < (py + ph) ) {

                        var index = obj.coordsKey.length - i - 1;

                        // HIGHLIGHT THE LINE HERE
                        context.beginPath();
                        context.strokeStyle = 'rgba(0,0,0,0.5)';
                        context.lineWidth  = obj.Get('chart.linewidth') + 2;
                        for (var j=0; j<obj.coords2[index].length; ++j) {

                            var x = obj.coords2[index][j][0];
                            var y = obj.coords2[index][j][1];

                            if (j == 0) {
                                context.moveTo(x, y);
                            } else {
                                context.lineTo(x, y);
                            }
                        }
                        context.stroke();


                        context.lineWidth  = 1;
                        context.beginPath();
                            context.strokeStyle = 'black';
                            context.fillStyle   = 'white';

                            RGraph.SetShadow(obj, 'rgba(0,0,0,0.5)', 0,0,10);

                            context.strokeRect(px - 2, py - 2, pw + 4, ph + 4);
                            context.fillRect(px - 2, py - 2, pw + 4, ph + 4);

                        context.stroke();
                        context.fill();


                        RGraph.NoShadow(obj);


                        context.beginPath();
                            context.fillStyle = obj.Get('chart.colors')[obj.Get('chart.colors').length - i - 1];
                            context.fillRect(px, py, blob_size, blob_size);
                        context.fill();

                        context.beginPath();
                            context.fillStyle = obj.Get('chart.text.color');

                            RGraph.Text(context,
                                        obj.Get('chart.text.font'),
                                        obj.Get('chart.text.size'),
                                        px + 5 + blob_size,
                                        py + ph,
                                        obj.Get('chart.key')[obj.Get('chart.key').length - i - 1]
                                       );
                        context.fill();


                        canvas.style.cursor = 'pointer';

                        return;
                    }

                    canvas.style.cursor = 'default';
                }
            }
            canvas.addEventListener('click', key_click, false);
            RGraph.AddEventListener(canvas.id, 'click', key_click);


            //var key_window_click = function (e)
            //{
            //    RGraph.Redraw();
            //}
            //window.addEventListener('click', key_window_click, false);
            //RGraph.AddEventListener(canvas.id, 'window_click', key_window_click);
        }
    }






    /**
    * This does the actual drawing of the key when it's in the gutter
    *
    * @param object obj The graph object
    * @param array  key The key items to draw
    * @param array colors An aray of colors that the key will use
    */
    RGraph.DrawKey_gutter = function (obj, key, colors)
    {
        var canvas      = obj.canvas;
        var context     = obj.context;
        var text_size   = typeof(obj.Get('chart.key.text.size')) == 'number' ? obj.Get('chart.key.text.size') : obj.Get('chart.text.size');
        var text_font   = obj.Get('chart.text.font');

        var gutterLeft   = obj.Get('chart.gutter.left');
        var gutterRight  = obj.Get('chart.gutter.right');
        var gutterTop    = obj.Get('chart.gutter.top');
        var gutterBottom = obj.Get('chart.gutter.bottom');

        var hpos        = RGraph.GetWidth(obj) / 2;
        var vpos        = (gutterTop / 2) - 5;
        var title       = obj.Get('chart.title');
        var blob_size   = text_size; // The blob of color
        var hmargin      = 8; // This is the size of the gaps between the blob of color and the text
        var vmargin      = 4; // This is the vertical margin of the key
        var fillstyle   = obj.Get('chart.key.background');
        var strokestyle = 'black';
        var length      = 0;



        // Need to work out the length of the key first
        context.font = text_size + 'pt ' + text_font;
        for (i=0; i<key.length; ++i) {
            length += hmargin;
            length += blob_size;
            length += hmargin;
            length += context.measureText(key[i]).width;
        }
        length += hmargin;




        /**
        * Work out hpos since in the Pie it isn't necessarily dead center
        */
        if (obj.type == 'pie') {
            if (obj.Get('chart.align') == 'left') {
                var hpos = obj.radius + gutterLeft;

            } else if (obj.Get('chart.align') == 'right') {
                var hpos = obj.canvas.width - obj.radius - gutterRight;

            } else {
                hpos = canvas.width / 2;
            }
        }





        /**
        * This makes the key centered
        */
        hpos -= (length / 2);


        /**
        * Override the horizontal/vertical positioning
        */
        if (typeof(obj.Get('chart.key.position.x')) == 'number') {
            hpos = obj.Get('chart.key.position.x');
        }
        if (typeof(obj.Get('chart.key.position.y')) == 'number') {
            vpos = obj.Get('chart.key.position.y');
        }



        /**
        * Draw the box that the key sits in
        */
        if (obj.Get('chart.key.position.gutter.boxed')) {

            if (obj.Get('chart.key.shadow')) {
                context.shadowColor   = obj.Get('chart.key.shadow.color');
                context.shadowBlur    = obj.Get('chart.key.shadow.blur');
                context.shadowOffsetX = obj.Get('chart.key.shadow.offsetx');
                context.shadowOffsetY = obj.Get('chart.key.shadow.offsety');
            }


            context.beginPath();
                context.fillStyle = fillstyle;
                context.strokeStyle = strokestyle;

                if (obj.Get('chart.key.rounded')) {
                    RGraph.strokedCurvyRect(context, hpos, vpos - vmargin, length, text_size + vmargin + vmargin)
                    // Odd... RGraph.filledCurvyRect(context, hpos, vpos - vmargin, length, text_size + vmargin + vmargin);
                } else {
                    context.strokeRect(hpos, vpos - vmargin, length, text_size + vmargin + vmargin);
                    context.fillRect(hpos, vpos - vmargin, length, text_size + vmargin + vmargin);
                }

            context.stroke();
            context.fill();


            RGraph.NoShadow(obj);
        }


        /**
        * Draw the blobs of color and the text
        */
        for (var i=0, pos=hpos; i<key.length; ++i) {
            pos += hmargin;

            // Draw the blob of color - line
            if (obj.Get('chart.key.color.shape') =='line') {

                context.beginPath();
                    context.strokeStyle = colors[i];
                    context.moveTo(pos, vpos + (blob_size / 2));
                    context.lineTo(pos + blob_size, vpos + (blob_size / 2));
                context.stroke();

            // Circle
            } else if (obj.Get('chart.key.color.shape') == 'circle') {

                context.beginPath();
                    context.fillStyle = colors[i];
                    context.moveTo(pos, vpos + (blob_size / 2));
                    context.arc(pos + (blob_size / 2), vpos + (blob_size / 2), (blob_size / 2), 0, 6.28, 0);
                context.fill();


            } else {

                context.beginPath();
                    context.fillStyle = colors[i];
                    context.fillRect(pos, vpos, blob_size, blob_size);
                context.fill();
            }

            pos += blob_size;

            pos += hmargin;

            context.beginPath();
                context.fillStyle = 'black';
                RGraph.Text(context, text_font, text_size, pos, vpos + text_size - 1, key[i]);
            context.fill();
            pos += context.measureText(key[i]).width;
        }
    }


    /**
    * Returns the key length, but accounts for null values
    *
    * @param array key The key elements
    */
    RGraph.getKeyLength = function (key)
    {
        var len = 0;

        for (var i=0; i<key.length; ++i) {
            if (key[i] != null) {
                ++len;
            }
        }

        return len;
    }






    /**
    * A shortcut for RGraph.pr()
    */
    function pd(variable)
    {
        RGraph.pr(variable);
    }

    function p(variable)
    {
        RGraph.pr(variable);
    }

    /**
    * A shortcut for console.log - as used by Firebug and Chromes console
    */
    function cl (variable)
    {
        return console.log(variable);
    }


    /**
    * Makes a clone of an object
    *
    * @param obj val The object to clone
    */
    RGraph.array_clone = function (obj)
    {
        if(obj == null || typeof(obj) != 'object') {
            return obj;
        }

        var temp = [];
        //var temp = new obj.constructor();

        for(var i=0;i<obj.length; ++i) {
            temp[i] = RGraph.array_clone(obj[i]);
        }

        return temp;
    }


    /**
    * This function reverses an array
    */
    RGraph.array_reverse = function (arr)
    {
        var newarr = [];

        for (var i=arr.length - 1; i>=0; i--) {
            newarr.push(arr[i]);
        }

        return newarr;
    }


    /**
    * Formats a number with thousand seperators so it's easier to read
    *
    * @param  integer num The number to format
    * @param  string      The (optional) string to prepend to the string
    * @param  string      The (optional) string to ap
    * pend to the string
    * @return string      The formatted number
    */
    RGraph.number_format = function (obj, num)
    {
        var i;
        var prepend = arguments[2] ? String(arguments[2]) : '';
        var append  = arguments[3] ? String(arguments[3]) : '';
        var output  = '';
        var decimal = '';
        var decimal_seperator  = obj.Get('chart.scale.point') ? obj.Get('chart.scale.point') : '.';
        var thousand_seperator = obj.Get('chart.scale.thousand') ? obj.Get('chart.scale.thousand') : ',';
        RegExp.$1   = '';
        var i,j;

if (typeof(obj.Get('chart.scale.formatter')) == 'function') {
    return obj.Get('chart.scale.formatter')(obj, num);
}

        // Ignore the preformatted version of "1e-2"
        if (String(num).indexOf('e') > 0) {
            return String(prepend + String(num) + append);
        }

        // We need then number as a string
        num = String(num);

        // Take off the decimal part - we re-append it later
        if (num.indexOf('.') > 0) {
            num     = num.replace(/\.(.*)/, '');
            decimal = RegExp.$1;
        }

        // Thousand seperator
        //var seperator = arguments[1] ? String(arguments[1]) : ',';
        var seperator = thousand_seperator;

        /**
        * Work backwards adding the thousand seperators
        */
        var foundPoint;
        for (i=(num.length - 1),j=0; i>=0; j++,i--) {
            var character = num.charAt(i);

            if ( j % 3 == 0 && j != 0) {
                output += seperator;
            }

            /**
            * Build the output
            */
            output += character;
        }

        /**
        * Now need to reverse the string
        */
        var rev = output;
        output = '';
        for (i=(rev.length - 1); i>=0; i--) {
            output += rev.charAt(i);
        }

        // Tidy up
        output = output.replace(/^-,/, '-');

        // Reappend the decimal
        if (decimal.length) {
            output =  output + decimal_seperator + decimal;
            decimal = '';
            RegExp.$1 = '';
        }

        // Minor bugette
        if (output.charAt(0) == '-') {
            output = output.replace(/-/, '');
            prepend = '-' + prepend;
        }

        return prepend + output + append;
    }


    /**
    * Draws horizontal coloured bars on something like the bar, line or scatter
    */
    RGraph.DrawBars = function (obj)
    {
        var hbars = obj.Get('chart.background.hbars');

        /**
        * Draws a horizontal bar
        */
        obj.context.beginPath();

        for (i=0; i<hbars.length; ++i) {

            // If null is specified as the "height", set it to the upper max value
            if (hbars[i][1] == null) {
                hbars[i][1] = obj.max;

            // If the first index plus the second index is greater than the max value, adjust accordingly
            } else if (hbars[i][0] + hbars[i][1] > obj.max) {
                hbars[i][1] = obj.max - hbars[i][0];
            }


            // If height is negative, and the abs() value is greater than .max, use a negative max instead
            if (Math.abs(hbars[i][1]) > obj.max) {
                hbars[i][1] = -1 * obj.max;
            }


            // If start point is greater than max, change it to max
            if (Math.abs(hbars[i][0]) > obj.max) {
                hbars[i][0] = obj.max;
            }

            // If start point plus height is less than negative max, use the negative max plus the start point
            if (hbars[i][0] + hbars[i][1] < (-1 * obj.max) ) {
                hbars[i][1] = -1 * (obj.max + hbars[i][0]);
            }

            // If the X axis is at the bottom, and a negative max is given, warn the user
            if (obj.Get('chart.xaxispos') == 'bottom' && (hbars[i][0] < 0 || (hbars[i][1] + hbars[i][1] < 0)) ) {
                alert('[' + obj.type.toUpperCase() + ' (ID: ' + obj.id + ') BACKGROUND HBARS] You have a negative value in one of your background hbars values, whilst the X axis is in the center');
            }

            var ystart = (obj.grapharea - ((hbars[i][0] / obj.max) * obj.grapharea));
            var height = (Math.min(hbars[i][1], obj.max - hbars[i][0]) / obj.max) * obj.grapharea;

            // Account for the X axis being in the center
            if (obj.Get('chart.xaxispos') == 'center') {
                ystart /= 2;
                height /= 2;
            }

            ystart += obj.Get('chart.gutter.top')

            var x = obj.Get('chart.gutter.left');
            var y = ystart - height;
            var w = obj.canvas.width - obj.Get('chart.gutter.left') - obj.Get('chart.gutter.right');
            var h = height;

            // Accommodate Opera :-/
            if (navigator.userAgent.indexOf('Opera') != -1 && obj.Get('chart.xaxispos') == 'center' && h < 0) {
                h *= -1;
                y = y - h;
            }

            /**
            * Account for X axis at the top
            */
            if (obj.Get('chart.xaxispos') == 'top') {
                y  = obj.canvas.height - y;
                h *= -1;
            }

            obj.context.fillStyle = hbars[i][2];
            obj.context.fillRect(x, y, w, h);
        }

        obj.context.fill();
    }


    /**
    * Draws in-graph labels.
    *
    * @param object obj The graph object
    */
    RGraph.DrawInGraphLabels = function (obj)
    {
        var canvas  = obj.canvas;
        var context = obj.context;
        var labels  = obj.Get('chart.labels.ingraph');
        var labels_processed = [];

        // Defaults
        var fgcolor   = 'black';
        var bgcolor   = 'white';
        var direction = 1;

        if (!labels) {
            return;
        }

        /**
        * Preprocess the labels array. Numbers are expanded
        */
        for (var i=0; i<labels.length; ++i) {
            if (typeof(labels[i]) == 'number') {
                for (var j=0; j<labels[i]; ++j) {
                    labels_processed.push(null);
                }
            } else if (typeof(labels[i]) == 'string' || typeof(labels[i]) == 'object') {
                labels_processed.push(labels[i]);

            } else {
                labels_processed.push('');
            }
        }

        /**
        * Turn off any shadow
        */
        RGraph.NoShadow(obj);

        if (labels_processed && labels_processed.length > 0) {

            for (var i=0; i<labels_processed.length; ++i) {
                if (labels_processed[i]) {
                    var coords = obj.coords[i];

                    if (coords && coords.length > 0) {
                        var x      = (obj.type == 'bar' ? coords[0] + (coords[2] / 2) : coords[0]);
                        var y      = (obj.type == 'bar' ? coords[1] + (coords[3] / 2) : coords[1]);
                        var length = typeof(labels_processed[i][4]) == 'number' ? labels_processed[i][4] : 25;


                        context.beginPath();
                        context.fillStyle   = 'black';
                        context.strokeStyle = 'black';


                        if (obj.type == 'bar') {

                            /**
                            * X axis at the top
                            */
                            if (obj.Get('chart.xaxispos') == 'top') {
                                length *= -1;
                            }

                            if (obj.Get('chart.variant') == 'dot') {
                                context.moveTo(x, obj.coords[i][1] - 5);
                                context.lineTo(x, obj.coords[i][1] - 5 - length);

                                var text_x = x;
                                var text_y = obj.coords[i][1] - 5 - length;

                            } else if (obj.Get('chart.variant') == 'arrow') {
                                context.moveTo(x, obj.coords[i][1] - 5);
                                context.lineTo(x, obj.coords[i][1] - 5 - length);

                                var text_x = x;
                                var text_y = obj.coords[i][1] - 5 - length;

                            } else {

                                context.arc(x, y, 2.5, 0, 6.28, 0);
                                context.moveTo(x, y);
                                context.lineTo(x, y - length);

                                var text_x = x;
                                var text_y = y - length;
                            }

                            context.stroke();
                            context.fill();


                        } else if (obj.type == 'line') {

                            if (
                                typeof(labels_processed[i]) == 'object' &&
                                typeof(labels_processed[i][3]) == 'number' &&
                                labels_processed[i][3] == -1
                               ) {

                                context.moveTo(x, y + 5);
                                context.lineTo(x, y + 5 + length);

                                context.stroke();
                                context.beginPath();

                                // This draws the arrow
                                context.moveTo(x, y + 5);
                                context.lineTo(x - 3, y + 10);
                                context.lineTo(x + 3, y + 10);
                                context.closePath();

                                var text_x = x;
                                var text_y = y + 5 + length;

                            } else {

                                var text_x = x;
                                var text_y = y - 5 - length;

                                context.moveTo(x, y - 5);
                                context.lineTo(x, y - 5 - length);

                                context.stroke();
                                context.beginPath();

                                // This draws the arrow
                                context.moveTo(x, y - 5);
                                context.lineTo(x - 3, y - 10);
                                context.lineTo(x + 3, y - 10);
                                context.closePath();
                            }

                            context.fill();
                        }

                        // Taken out on the 10th Nov 2010 - unnecessary
                        //var width = context.measureText(labels[i]).width;

                        context.beginPath();

                            // Fore ground color
                            context.fillStyle = (typeof(labels_processed[i]) == 'object' && typeof(labels_processed[i][1]) == 'string') ? labels_processed[i][1] : 'black';

                            RGraph.Text(context,
                                        obj.Get('chart.text.font'),
                                        obj.Get('chart.text.size'),
                                        text_x,
                                        text_y,
                                        (typeof(labels_processed[i]) == 'object' && typeof(labels_processed[i][0]) == 'string') ? labels_processed[i][0] : labels_processed[i],
                                        'bottom',
                                        'center',
                                        true,
                                        null,
                                        (typeof(labels_processed[i]) == 'object' && typeof(labels_processed[i][2]) == 'string') ? labels_processed[i][2] : 'white');
                        context.fill();
                    }
                }
            }
        }
    }


    /**
    * This function "fills in" key missing properties that various implementations lack
    *
    * @param object e The event object
    */
    RGraph.FixEventObject = function (e)
    {
        if (RGraph.isIE8()) {

            var e = event;

            e.pageX  = (event.clientX + document.body.scrollLeft);
            e.pageY  = (event.clientY + document.body.scrollTop);
            e.target = event.srcElement;

            if (!document.body.scrollTop && document.documentElement.scrollTop) {
                e.pageX += parseInt(document.documentElement.scrollLeft);
                e.pageY += parseInt(document.documentElement.scrollTop);
            }
        }

        // This is mainly for FF which doesn't provide offsetX
        if (typeof(e.offsetX) == 'undefined' && typeof(e.offsetY) == 'undefined') {
            var coords = RGraph.getMouseXY(e);
            e.offsetX = coords[0];
            e.offsetY = coords[1];
        }

        // Any browser that doesn't implement stopPropagation() (MSIE)
        if (!e.stopPropagation) {
            e.stopPropagation = function () {window.event.cancelBubble = true;}
        }

        return e;
    }


    /**
    * Draw crosshairs if enabled
    *
    * @param object obj The graph object (from which we can get the context and canvas as required)
    */
    RGraph.DrawCrosshairs = function (obj)
    {
        if (obj.Get('chart.crosshairs')) {
            var canvas  = obj.canvas;
            var context = obj.context;

            // 5th November 2010 - removed now that tooltips are DOM2 based.
            //if (obj.Get('chart.tooltips') && obj.Get('chart.tooltips').length > 0) {
                //alert('[' + obj.type.toUpperCase() + '] Sorry - you cannot have crosshairs enabled with tooltips! Turning off crosshairs...');
                //obj.Set('chart.crosshairs', false);
                //return;
            //}

            canvas.onmousemove = function (e)
            {
                var e       = RGraph.FixEventObject(e);
                var canvas  = obj.canvas;
                var context = obj.context;
                var width   = canvas.width;
                var height  = canvas.height;
                var adjustments = obj.Get('chart.tooltips.coords.adjust');

                var gutterLeft   = obj.Get('chart.gutter.left');
                var gutterRight  = obj.Get('chart.gutter.right');
                var gutterTop    = obj.Get('chart.gutter.top');
                var gutterBottom = obj.Get('chart.gutter.bottom');

                var mouseCoords = RGraph.getMouseXY(e);
                var x = mouseCoords[0];
                var y = mouseCoords[1];

                if (typeof(adjustments) == 'object' && adjustments[0] && adjustments[1]) {
                    x = x - adjustments[0];
                    y = y - adjustments[1];
                }

                RGraph.Clear(canvas);
                obj.Draw();

                if (   x >= gutterLeft
                    && y >= gutterTop
                    && x <= (width - gutterRight)
                    && y <= (height - gutterBottom)
                   ) {

                    var linewidth = obj.Get('chart.crosshairs.linewidth');
                    context.lineWidth = linewidth ? linewidth : 1;

                    context.beginPath();
                    context.strokeStyle = obj.Get('chart.crosshairs.color');

                    // Draw a top vertical line
                    context.moveTo(x, gutterTop);
                    context.lineTo(x, height - gutterBottom);

                    // Draw a horizontal line
                    context.moveTo(gutterLeft, y);
                    context.lineTo(width - gutterRight, y);

                    context.stroke();

                    /**
                    * Need to show the coords?
                    */
                    if (obj.Get('chart.crosshairs.coords')) {
                        if (obj.type == 'scatter') {

                            var xCoord = (((x - obj.Get('chart.gutter.left')) / (obj.canvas.width - gutterLeft - gutterRight)) * (obj.Get('chart.xmax') - obj.Get('chart.xmin'))) + obj.Get('chart.xmin');
                                xCoord = xCoord.toFixed(obj.Get('chart.scale.decimals'));
                            var yCoord = obj.max - (((y - obj.Get('chart.gutter.top')) / (obj.canvas.height - gutterTop - gutterBottom)) * obj.max);

                                if (obj.type == 'scatter' && obj.Get('chart.xaxispos') == 'center') {
                                    yCoord = (yCoord - (obj.max / 2)) * 2;
                                }

                                yCoord = yCoord.toFixed(obj.Get('chart.scale.decimals'));
                            var div    = RGraph.Registry.Get('chart.coordinates.coords.div');
                            var mouseCoords = RGraph.getMouseXY(e);
                            var canvasXY = RGraph.getCanvasXY(canvas);

                            if (!div) {

                                div = document.createElement('DIV');
                                div.__object__     = obj;
                                div.style.position = 'absolute';
                                div.style.backgroundColor = 'white';
                                div.style.border = '1px solid black';
                                div.style.fontFamily = 'Arial, Verdana, sans-serif';
                                div.style.fontSize = '10pt'
                                div.style.padding = '2px';
                                div.style.opacity = 1;
                                div.style.WebkitBorderRadius = '3px';
                                div.style.borderRadius = '3px';
                                div.style.MozBorderRadius = '3px';
                                document.body.appendChild(div);

                                RGraph.Registry.Set('chart.coordinates.coords.div', div);
                            }

                            // Convert the X/Y pixel coords to correspond to the scale

                            div.style.opacity = 1;
                            div.style.display = 'inline';

                            if (!obj.Get('chart.crosshairs.coords.fixed')) {
                                div.style.left = Math.max(2, (e.pageX - div.offsetWidth - 3)) + 'px';
                                div.style.top = Math.max(2, (e.pageY - div.offsetHeight - 3))  + 'px';
                            } else {
                                div.style.left = canvasXY[0] + gutterLeft + 3 + 'px';
                                div.style.top  = canvasXY[1] + gutterTop + 3 + 'px';
                            }

                            div.innerHTML = '<span style="color: #666">' + obj.Get('chart.crosshairs.coords.labels.x') + ':</span> ' + xCoord + '<br><span style="color: #666">' + obj.Get('chart.crosshairs.coords.labels.y') + ':</span> ' + yCoord;

                            canvas.addEventListener('mouseout', RGraph.HideCrosshairCoords, false);

                        } else {
                            alert('[RGRAPH] Showing crosshair coordinates is only supported on the Scatter chart');
                        }
                    }
                } else {
                    RGraph.HideCrosshairCoords();
                }
            }
        }
    }

    /**
    * Thisz function hides the crosshairs coordinates
    */
    RGraph.HideCrosshairCoords = function ()
    {
        var div = RGraph.Registry.Get('chart.coordinates.coords.div');

        if (   div
            && div.style.opacity == 1
            && div.__object__.Get('chart.crosshairs.coords.fadeout')
           ) {
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.opacity = 0.9;}, 50);
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.opacity = 0.8;}, 100);
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.opacity = 0.7;}, 150);
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.opacity = 0.6;}, 200);
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.opacity = 0.5;}, 250);
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.opacity = 0.4;}, 300);
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.opacity = 0.3;}, 350);
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.opacity = 0.2;}, 400);
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.opacity = 0.1;}, 450);
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.opacity = 0;}, 500);
            setTimeout(function() {RGraph.Registry.Get('chart.coordinates.coords.div').style.display = 'none';}, 550);
        }
    }


    /**
    * Trims the right hand side of a string. Removes SPACE, TAB
    * CR and LF.
    *
    * @param string str The string to trim
    */
    RGraph.rtrim = function (str)
    {
        return str.replace(/( |\n|\r|\t)+$/, '');
    }


    /**
    * Draws the3D axes/background
    */
    RGraph.Draw3DAxes = function (obj)
    {
        var gutterLeft    = obj.Get('chart.gutter.left');
        var gutterRight   = obj.Get('chart.gutter.right');
        var gutterTop     = obj.Get('chart.gutter.top');
        var gutterBottom  = obj.Get('chart.gutter.bottom');

        var context = obj.context;
        var canvas  = obj.canvas;

        context.strokeStyle = '#aaa';
        context.fillStyle = '#ddd';

        // Draw the vertical left side
        context.beginPath();
            context.moveTo(gutterLeft, gutterTop);
            context.lineTo(gutterLeft + 10, gutterTop - 5);
            context.lineTo(gutterLeft + 10, canvas.height - gutterBottom - 5);
            context.lineTo(gutterLeft, canvas.height - gutterBottom);
        context.closePath();

        context.stroke();
        context.fill();

        // Draw the bottom floor
        context.beginPath();
            context.moveTo(gutterLeft, canvas.height - gutterBottom);
            context.lineTo(gutterLeft + 10, canvas.height - gutterBottom - 5);
            context.lineTo(canvas.width - gutterRight + 10,  canvas.height - gutterBottom - 5);
            context.lineTo(canvas.width - gutterRight, canvas.height - gutterBottom);
        context.closePath();

        context.stroke();
        context.fill();
    }

    /**
    * Turns off any shadow
    *
    * @param object obj The graph object
    */
    RGraph.NoShadow = function (obj)
    {
        obj.context.shadowColor   = 'rgba(0,0,0,0)';
        obj.context.shadowBlur    = 0;
        obj.context.shadowOffsetX = 0;
        obj.context.shadowOffsetY = 0;
    }


    /**
    * Sets the four shadow properties - a shortcut function
    *
    * @param object obj     Your graph object
    * @param string color   The shadow color
    * @param number offsetx The shadows X offset
    * @param number offsety The shadows Y offset
    * @param number blur    The blurring effect applied to the shadow
    */
    RGraph.SetShadow = function (obj, color, offsetx, offsety, blur)
    {
        obj.context.shadowColor   = color;
        obj.context.shadowOffsetX = offsetx;
        obj.context.shadowOffsetY = offsety;
        obj.context.shadowBlur    = blur;
    }


    /**
    * This function attempts to "fill in" missing functions from the canvas
    * context object. Only two at the moment - measureText() nd fillText().
    *
    * @param object context The canvas 2D context
    */
    RGraph.OldBrowserCompat = function (context)
    {
        if (!context.measureText) {

            // This emulates the measureText() function
            context.measureText = function (text)
            {
                var textObj = document.createElement('DIV');
                textObj.innerHTML = text;
                textObj.style.backgroundColor = 'white';
                textObj.style.position = 'absolute';
                textObj.style.top = -100
                textObj.style.left = 0;
                document.body.appendChild(textObj);

                var width = {width: textObj.offsetWidth};

                textObj.style.display = 'none';

                return width;
            }
        }

        if (!context.fillText) {
            // This emulates the fillText() method
            context.fillText    = function (text, targetX, targetY)
            {
                return false;
            }
        }

        // If IE8, add addEventListener()
        if (!context.canvas.addEventListener) {
            window.addEventListener = function (ev, func, bubble)
            {
                return this.attachEvent('on' + ev, func);
            }

            context.canvas.addEventListener = function (ev, func, bubble)
            {
                return this.attachEvent('on' + ev, func);
            }
        }
    }



    /**
    * This is a function that can be used to run code asynchronously, which can
    * be used to speed up the loading of you pages.
    *
    * @param string func This is the code to run. It can also be a function pointer.
    *                    The front page graphs show this function in action. Basically
    *                   each graphs code is made in a function, and that function is
    *                   passed to this function to run asychronously.
    */
    RGraph.Async = function (func)
    {
        return setTimeout(func, arguments[1] ? arguments[1] : 1);
    }


    /**
    * A custom random number function
    *
    * @param number min The minimum that the number should be
    * @param number max The maximum that the number should be
    * @param number    How many decimal places there should be. Default for this is 0
    */
    RGraph.random = function (min, max)
    {
        var dp = arguments[2] ? arguments[2] : 0;
        var r = Math.random();

        return Number((((max - min) * r) + min).toFixed(dp));
    }


    /**
    * Draws a rectangle with curvy corners
    *
    * @param context object The context
    * @param x       number The X coordinate (top left of the square)
    * @param y       number The Y coordinate (top left of the square)
    * @param w       number The width of the rectangle
    * @param h       number The height of the rectangle
    * @param         number The radius of the curved corners
    * @param         boolean Whether the top left corner is curvy
    * @param         boolean Whether the top right corner is curvy
    * @param         boolean Whether the bottom right corner is curvy
    * @param         boolean Whether the bottom left corner is curvy
    */
    RGraph.strokedCurvyRect = function (context, x, y, w, h)
    {
        // The corner radius
        var r = arguments[5] ? arguments[5] : 3;

        // The corners
        var corner_tl = (arguments[6] || arguments[6] == null) ? true : false;
        var corner_tr = (arguments[7] || arguments[7] == null) ? true : false;
        var corner_br = (arguments[8] || arguments[8] == null) ? true : false;
        var corner_bl = (arguments[9] || arguments[9] == null) ? true : false;

        context.beginPath();

            // Top left side
            context.moveTo(x + (corner_tl ? r : 0), y);
            context.lineTo(x + w - (corner_tr ? r : 0), y);

            // Top right corner
            if (corner_tr) {
                context.arc(x + w - r, y + r, r, Math.PI * 1.5, Math.PI * 2, false);
            }

            // Top right side
            context.lineTo(x + w, y + h - (corner_br ? r : 0) );

            // Bottom right corner
            if (corner_br) {
                context.arc(x + w - r, y - r + h, r, Math.PI * 2, Math.PI * 0.5, false);
            }

            // Bottom right side
            context.lineTo(x + (corner_bl ? r : 0), y + h);

            // Bottom left corner
            if (corner_bl) {
                context.arc(x + r, y - r + h, r, Math.PI * 0.5, Math.PI, false);
            }

            // Bottom left side
            context.lineTo(x, y + (corner_tl ? r : 0) );

            // Top left corner
            if (corner_tl) {
                context.arc(x + r, y + r, r, Math.PI, Math.PI * 1.5, false);
            }

        context.stroke();
    }


    /**
    * Draws a filled rectangle with curvy corners
    *
    * @param context object The context
    * @param x       number The X coordinate (top left of the square)
    * @param y       number The Y coordinate (top left of the square)
    * @param w       number The width of the rectangle
    * @param h       number The height of the rectangle
    * @param         number The radius of the curved corners
    * @param         boolean Whether the top left corner is curvy
    * @param         boolean Whether the top right corner is curvy
    * @param         boolean Whether the bottom right corner is curvy
    * @param         boolean Whether the bottom left corner is curvy
    */
    RGraph.filledCurvyRect = function (context, x, y, w, h)
    {
        // The corner radius
        var r = arguments[5] ? arguments[5] : 3;

        // The corners
        var corner_tl = (arguments[6] || arguments[6] == null) ? true : false;
        var corner_tr = (arguments[7] || arguments[7] == null) ? true : false;
        var corner_br = (arguments[8] || arguments[8] == null) ? true : false;
        var corner_bl = (arguments[9] || arguments[9] == null) ? true : false;

        context.beginPath();

            // First draw the corners

            // Top left corner
            if (corner_tl) {
                context.moveTo(x + r, y + r);
                context.arc(x + r, y + r, r, Math.PI, 1.5 * Math.PI, false);
            } else {
                context.fillRect(x, y, r, r);
            }

            // Top right corner
            if (corner_tr) {
                context.moveTo(x + w - r, y + r);
                context.arc(x + w - r, y + r, r, 1.5 * Math.PI, 0, false);
            } else {
                context.moveTo(x + w - r, y);
                context.fillRect(x + w - r, y, r, r);
            }


            // Bottom right corner
            if (corner_br) {
                context.moveTo(x + w - r, y + h - r);
                context.arc(x + w - r, y - r + h, r, 0, Math.PI / 2, false);
            } else {
                context.moveTo(x + w - r, y + h - r);
                context.fillRect(x + w - r, y + h - r, r, r);
            }

            // Bottom left corner
            if (corner_bl) {
                context.moveTo(x + r, y + h - r);
                context.arc(x + r, y - r + h, r, Math.PI / 2, Math.PI, false);
            } else {
                context.moveTo(x, y + h - r);
                context.fillRect(x, y + h - r, r, r);
            }

            // Now fill it in
            context.fillRect(x + r, y, w - r - r, h);
            context.fillRect(x, y + r, r + 1, h - r - r);
            context.fillRect(x + w - r - 1, y + r, r + 1, h - r - r);

        context.fill();
    }


    /**
    * A crude timing function
    *
    * @param string label The label to use for the time
    */
    RGraph.Timer = function (label)
    {
        var d = new Date();

        // This uses the Firebug console
        console.log(label + ': ' + d.getSeconds() + '.' + d.getMilliseconds());
    }


    /**
    * Hides the palette if it's visible
    */
    RGraph.HidePalette = function ()
    {
        var div = RGraph.Registry.Get('palette');

        if (typeof(div) == 'object' && div) {
            div.style.visibility = 'hidden';
            div.style.display    = 'none';
            RGraph.Registry.Set('palette', null);
        }
    }


    /**
    * Hides the zoomed canvas
    */
    RGraph.HideZoomedCanvas = function ()
    {
        if (typeof(__zoomedimage__) == 'object') {
            obj = __zoomedimage__.obj;
        } else {
            return;
        }

        if (obj.Get('chart.zoom.fade.out')) {
            for (var i=10,j=1; i>=0; --i, ++j) {
                if (typeof(__zoomedimage__) == 'object') {
                    setTimeout("__zoomedimage__.style.opacity = " + String(i / 10), j * 30);
                }
            }

            if (typeof(__zoomedbackground__) == 'object') {
                setTimeout("__zoomedbackground__.style.opacity = " + String(i / 10), j * 30);
            }
        }

        if (typeof(__zoomedimage__) == 'object') {
            setTimeout("__zoomedimage__.style.display = 'none'", obj.Get('chart.zoom.fade.out') ? 310 : 0);
        }

        if (typeof(__zoomedbackground__) == 'object') {
            setTimeout("__zoomedbackground__.style.display = 'none'", obj.Get('chart.zoom.fade.out') ? 310 : 0);
        }
    }


    /**
    * Adds an event handler
    *
    * @param object obj   The graph object
    * @param string event The name of the event, eg ontooltip
    * @param object func  The callback function
    */
    RGraph.AddCustomEventListener = function (obj, name, func)
    {
        if (typeof(RGraph.events[obj.id]) == 'undefined') {
            RGraph.events[obj.id] = [];
        }

        RGraph.events[obj.id].push([obj, name, func]);

        return RGraph.events[obj.id].length - 1;
    }


    /**
    * Used to fire one of the RGraph custom events
    *
    * @param object obj   The graph object that fires the event
    * @param string event The name of the event to fire
    */
    RGraph.FireCustomEvent = function (obj, name)
    {
        if (obj && obj.isRGraph) {
            var id = obj.id;

            if (   typeof(id) == 'string'
                && typeof(RGraph.events) == 'object'
                && typeof(RGraph.events[id]) == 'object'
                && RGraph.events[id].length > 0) {

                for(var j=0; j<RGraph.events[id].length; ++j) {
                    if (RGraph.events[id][j] && RGraph.events[id][j][1] == name) {
                        RGraph.events[id][j][2](obj);
                    }
                }
            }
        }
    }


    /**
    * Checks the browser for traces of MSIE8
    */
    RGraph.isIE8 = function ()
    {
        return navigator.userAgent.indexOf('MSIE 8') > 0;
    }


    /**
    * Checks the browser for traces of MSIE9
    */
    RGraph.isIE9 = function ()
    {
        return navigator.userAgent.indexOf('MSIE 9') > 0;
    }


    /**
    * Checks the browser for traces of MSIE9
    */
    RGraph.isIE9up = function ()
    {
        navigator.userAgent.match(/MSIE (\d+)/);

        return Number(RegExp.$1) >= 9;
    }


    /**
    * This clears a canvases event handlers. Used at the start of each graphs .Draw() method.
    *
    * @param string id The ID of the canvas whose event handlers will be cleared
    */
    RGraph.ClearEventListeners = function (id)
    {
        for (var i=0; i<RGraph.Registry.Get('chart.event.handlers').length; ++i) {

            var el = RGraph.Registry.Get('chart.event.handlers')[i];

            if (el && (el[0] == id || el[0] == ('window_' + id)) ) {
                if (el[0].substring(0, 7) == 'window_') {
                    window.removeEventListener(el[1], el[2], false);
                } else {
                    document.getElementById(id).removeEventListener(el[1], el[2], false);
                }

                RGraph.Registry.Get('chart.event.handlers')[i] = null;
            }
        }
    }


    /**
    *
    */
    RGraph.AddEventListener = function (id, e, func)
    {
        RGraph.Registry.Get('chart.event.handlers').push([id, e, func]);
    }


    /**
    * This function suggests a gutter size based on the widest left label. Given that the bottom
    * labels may be longer, this may be a little out.
    *
    * @param object obj  The graph object
    * @param array  data An array of graph data
    * @return int        A suggested gutter setting
    */
    RGraph.getGutterSuggest = function (obj, data)
    {
        var str = RGraph.number_format(obj, RGraph.array_max(RGraph.getScale(RGraph.array_max(data), obj)), obj.Get('chart.units.pre'), obj.Get('chart.units.post'));

        // Take into account the HBar
        if (obj.type == 'hbar') {

            var str = '';
            var len = 0;

            for (var i=0; i<obj.Get('chart.labels').length; ++i) {
                str = (obj.Get('chart.labels').length > str.length ? obj.Get('chart.labels')[i] : str);
            }
        }

        obj.context.font = obj.Get('chart.text.size') + 'pt ' + obj.Get('chart.text.font');

        len = obj.context.measureText(str).width + 5;

        return (obj.type == 'hbar' ? len / 3 : len);
    }


    /**
    * A basic Array shift gunction
    *
    * @param  object The numerical array to work on
    * @return        The new array
    */
    RGraph.array_shift = function (arr)
    {
        var ret = [];

        for (var i=1; i<arr.length; ++i) ret.push(arr[i]);

        return ret;
    }


    /**
    * If you prefer, you can use the SetConfig() method to set the configuration information
    * for your chart. You may find that setting the configuration this way eases reuse.
    *
    * @param object obj    The graph object
    * @param object config The graph configuration information
    */
    RGraph.SetConfig = function (obj, c)
    {
        for (i in c) {
            if (typeof(i) == 'string') {
                obj.Set(i, c[i]);
            }
        }

        return obj;
    }


    /**
    * This function gets the canvas height. Defaults to the actual
    * height but this can be changed by setting chart.height.
    *
    * @param object obj The graph object
    */
    RGraph.GetHeight = function (obj)
    {
        return obj.canvas.height;
    }


    /**
    * This function gets the canvas width. Defaults to the actual
    * width but this can be changed by setting chart.width.
    *
    * @param object obj The graph object
    */
    RGraph.GetWidth = function (obj)
    {
        return obj.canvas.width;
    }


    /**
    * Clears all the custom event listeners that have been registered
    *
    * @param    string Limits the clearing to this object ID
    */
    RGraph.RemoveAllCustomEventListeners = function ()
    {
        var id = arguments[0];

        if (id && RGraph.events[id]) {
            RGraph.events[id] = [];
        } else {
            RGraph.events = [];
        }
    }


    /**
    * Clears a particular custom event listener
    *
    * @param object obj The graph object
    * @param number i   This is the index that is return by .AddCustomEventListener()
    */
    RGraph.RemoveCustomEventListener = function (obj, i)
    {
        if (   typeof(RGraph.events) == 'object'
            && typeof(RGraph.events[obj.id]) == 'object'
            && typeof(RGraph.events[obj.id][i]) == 'object') {

            RGraph.events[obj.id][i] = null;
        }
    }


    /**
    * This draws the background
    *
    * @param object obj The graph object
    */
    RGraph.DrawBackgroundImage = function (obj)
    {
        var img = new Image();
        img.__object__  = obj;
        img.__canvas__  = obj.canvas;
        img.__context__ = obj.context;
        img.src         = obj.Get('chart.background.image');

        obj.__background_image__ = img;

        img.onload = function ()
        {
            var obj = this.__object__;

            var gutterLeft   = obj.Get('chart.gutter.left');
            var gutterRight  = obj.Get('chart.gutter.right');
            var gutterTop    = obj.Get('chart.gutter.top');
            var gutterBottom = obj.Get('chart.gutter.bottom');

            RGraph.Clear(obj.canvas);

            obj.context.drawImage(this,gutterLeft,gutterTop, RGraph.GetWidth(obj) - gutterLeft - gutterRight, RGraph.GetHeight(obj) - gutterTop - gutterBottom);

            // Draw the graph
            obj.Draw();
        }

        img.onerror = function ()
        {
            var obj = this.__canvas__.__object__;

            // Show an error alert
            alert('[ERROR] There was an error with the background image that you specified: ' + img.src);

            // Draw the graph, because the onload doesn't fire, and thus that won't draw the chart
            obj.Draw();
        }
    }


    /**
    * This resets the canvas. Keep in mind that any translate() that has been performed will also be reset.
    *
    * @param object canvas The canvas
    */
    RGraph.Reset = function (canvas)
    {
        canvas.width = canvas.width;
    }