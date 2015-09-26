/* Menucool Custom Color Picker v2012.8.30. http://www.menucool.com/color-picker */

var MC = MC || {};
// Custom add function : convert RGB result to Hex
MC.rgbToHex = function(color) {
    if (color.substr(0, 1) === '#') {
        return color;
    }
    var digits = /(.*?)rgb\((\d+), (\d+), (\d+)\)/.exec(color);

    var red = parseInt(digits[2]);
    var green = parseInt(digits[3]);
    var blue = parseInt(digits[4]);

    var rgb = blue | (green << 8) | (red << 16);
    return digits[1] + '#' + rgb.toString(16);
};
MC.cC = function(a, b, c) {
    typeof OnCustomColorChanged !== "undefined" && OnCustomColorChanged(a, b, c)
};
MC.CustomColorPicker = function() {
    "use strict";
    var f = function(a, b, c) {
            if (a.addEventListener) a.addEventListener(b, c, 0);
            else if (a.attachEvent) a.attachEvent("on" + b, c);
            else a["on" + b] = c
        },
        d = function(a) {
            a.cancelBubble = true;
            a.stopPropagation && a.stopPropagation();
            a.preventDefault && a.preventDefault();
            if (window.event) a.returnValue = 0;
            if (a.cancel) a.cancel = 1
        },
        c = function(a) {
            if (!a) return 0;
            var b = /(^| )colorpicker( |$)/;
            return b.test(a)
        },
        b = function(a, b) {
            this.i = b;
            this.a = this.b = this.c = this.d = this.f = null;
            this.r = a;
            this.g()
        };
    b.prototype = {
        g: function() {
            if (this.r) {
                // this.r.style.display = "inline-block";
                for (var b = this.r.getElementsByTagName("span"), a = 0; a < b.length; a++)
                    if (b[a].className == "hexbox") this.a = b[a];
                    else if (b[a].className == "bgbox") this.b = b[a];
                else if (b[a].className == "colorbox") this.c = b[a];
                this.d = this.c.getElementsByTagName("b");
                for (var c = this, a = 0; a < this.d.length; a++) {
                    if (this.d[a].className == "selected") {
                        this.f = this.d[a];
                        this.h(this.d[a])
                    }
                    this.d[a].onmouseout = function() {
                        c.h(c.f)
                    }
                }
                this.j();
                this.r.setAttribute("href", "#")
            }
        },
        h: function(a) {
            if (this.a) this.a.innerHTML = a.title ? a.title : a.style.backgroundColor;
            if (this.b) this.b.style.backgroundColor = a.style.backgroundColor
        },
        j: function() {
            var a = this;
            this.c.onmouseover = function(b) {
                a.k(b, a)
            };
            this.c.onclick = function(b) {
                a.k(b, a, 1)
            }
        },
        k: function(c, b, f) {
            if (c.target) var a = c.target;
            else a = c.srcElement;
            if (a.nodeName == "B") {
                if (b.b) b.b.style.backgroundColor = a.style.backgroundColor;
                if (b.a) b.a.innerHTML = a.title ? a.title : a.style.backgroundColor;
                if (f) {
                    b.f = a;
                    a.className = "selected";
                    for (var e = 0; e < this.d.length; e++)
                        if (this.d[e] != a) this.d[e].className = "";
                    MC.cC(a.style.backgroundColor, a.title ? a.title : a.style.backgroundColor, b.i)
                }
            }
            d(c)
        }
    };
    var a = function() {
            for (var d = document.getElementsByTagName("span"), e = [], a = 0; a < d.length; a++)
                if (c(d[a].className)) {
                    var f = e.length;
                    e[f] = new b(d[a], f)
                }
            typeof OnCustomColorPickerLoaded !== "undefined" && OnCustomColorPickerLoaded()
        },
        e = function(c) {
            var a = false;

            function b() {
                if (a) return;
                a = true;
                setTimeout(c, 4)
            }
            document.addEventListener && document.addEventListener("DOMContentLoaded", b, false);
            f(window, "load", b)
        };
    e(a);
    return {
        reload: a
    }
}()
