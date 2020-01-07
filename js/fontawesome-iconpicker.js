/*!
 * Font Awesome Icon Picker
 * https://farbelous.github.io/fontawesome-iconpicker/
 *
 * Originally written by (c) 2016 Javi Aguilar
 * Licensed under the MIT License
 * https://github.com/farbelous/fontawesome-iconpicker/blob/master/LICENSE
 *
 */


(function(a) {
    if (typeof define === "function" && define.amd) {
        define([ "jquery" ], a);
    } else {
        a(jQuery);
    }
})(function(a) {
    a.ui = a.ui || {};
    var b = a.ui.version = "1.12.1";
    /*!
     * jQuery UI Position 1.12.1
     * http://jqueryui.com
     *
     * Copyright jQuery Foundation and other contributors
     * Released under the MIT license.
     * http://jquery.org/license
     *
     * http://api.jqueryui.com/position/
     */
    (function() {
        var b, c = Math.max, d = Math.abs, e = /left|center|right/, f = /top|center|bottom/, g = /[\+\-]\d+(\.[\d]+)?%?/, h = /^\w+/, i = /%$/, j = a.fn.pos;
        function k(a, b, c) {
            return [ parseFloat(a[0]) * (i.test(a[0]) ? b / 100 : 1), parseFloat(a[1]) * (i.test(a[1]) ? c / 100 : 1) ];
        }
        function l(b, c) {
            return parseInt(a.css(b, c), 10) || 0;
        }
        function m(b) {
            var c = b[0];
            if (c.nodeType === 9) {
                return {
                    width: b.width(),
                    height: b.height(),
                    offset: {
                        top: 0,
                        left: 0
                    }
                };
            }
            if (a.isWindow(c)) {
                return {
                    width: b.width(),
                    height: b.height(),
                    offset: {
                        top: b.scrollTop(),
                        left: b.scrollLeft()
                    }
                };
            }
            if (c.preventDefault) {
                return {
                    width: 0,
                    height: 0,
                    offset: {
                        top: c.pageY,
                        left: c.pageX
                    }
                };
            }
            return {
                width: b.outerWidth(),
                height: b.outerHeight(),
                offset: b.offset()
            };
        }
        a.pos = {
            scrollbarWidth: function() {
                if (b !== undefined) {
                    return b;
                }
                var c, d, e = a("<div " + "style='display:block;position:absolute;width:50px;height:50px;overflow:hidden;'>" + "<div style='height:100px;width:auto;'></div></div>"), f = e.children()[0];
                a("body").append(e);
                c = f.offsetWidth;
                e.css("overflow", "scroll");
                d = f.offsetWidth;
                if (c === d) {
                    d = e[0].clientWidth;
                }
                e.remove();
                return b = c - d;
            },
            getScrollInfo: function(b) {
                var c = b.isWindow || b.isDocument ? "" : b.element.css("overflow-x"), d = b.isWindow || b.isDocument ? "" : b.element.css("overflow-y"), e = c === "scroll" || c === "auto" && b.width < b.element[0].scrollWidth, f = d === "scroll" || d === "auto" && b.height < b.element[0].scrollHeight;
                return {
                    width: f ? a.pos.scrollbarWidth() : 0,
                    height: e ? a.pos.scrollbarWidth() : 0
                };
            },
            getWithinInfo: function(b) {
                var c = a(b || window), d = a.isWindow(c[0]), e = !!c[0] && c[0].nodeType === 9, f = !d && !e;
                return {
                    element: c,
                    isWindow: d,
                    isDocument: e,
                    offset: f ? a(b).offset() : {
                        left: 0,
                        top: 0
                    },
                    scrollLeft: c.scrollLeft(),
                    scrollTop: c.scrollTop(),
                    width: c.outerWidth(),
                    height: c.outerHeight()
                };
            }
        };
        a.fn.pos = function(b) {
            if (!b || !b.of) {
                return j.apply(this, arguments);
            }
            b = a.extend({}, b);
            var i, n, o, p, q, r, s = a(b.of), t = a.pos.getWithinInfo(b.within), u = a.pos.getScrollInfo(t), v = (b.collision || "flip").split(" "), w = {};
            r = m(s);
            if (s[0].preventDefault) {
                b.at = "left top";
            }
            n = r.width;
            o = r.height;
            p = r.offset;
            q = a.extend({}, p);
            a.each([ "my", "at" ], function() {
                var a = (b[this] || "").split(" "), c, d;
                if (a.length === 1) {
                    a = e.test(a[0]) ? a.concat([ "center" ]) : f.test(a[0]) ? [ "center" ].concat(a) : [ "center", "center" ];
                }
                a[0] = e.test(a[0]) ? a[0] : "center";
                a[1] = f.test(a[1]) ? a[1] : "center";
                c = g.exec(a[0]);
                d = g.exec(a[1]);
                w[this] = [ c ? c[0] : 0, d ? d[0] : 0 ];
                b[this] = [ h.exec(a[0])[0], h.exec(a[1])[0] ];
            });
            if (v.length === 1) {
                v[1] = v[0];
            }
            if (b.at[0] === "right") {
                q.left += n;
            } else if (b.at[0] === "center") {
                q.left += n / 2;
            }
            if (b.at[1] === "bottom") {
                q.top += o;
            } else if (b.at[1] === "center") {
                q.top += o / 2;
            }
            i = k(w.at, n, o);
            q.left += i[0];
            q.top += i[1];
            return this.each(function() {
                var e, f, g = a(this), h = g.outerWidth(), j = g.outerHeight(), m = l(this, "marginLeft"), r = l(this, "marginTop"), x = h + m + l(this, "marginRight") + u.width, y = j + r + l(this, "marginBottom") + u.height, z = a.extend({}, q), A = k(w.my, g.outerWidth(), g.outerHeight());
                if (b.my[0] === "right") {
                    z.left -= h;
                } else if (b.my[0] === "center") {
                    z.left -= h / 2;
                }
                if (b.my[1] === "bottom") {
                    z.top -= j;
                } else if (b.my[1] === "center") {
                    z.top -= j / 2;
                }
                z.left += A[0];
                z.top += A[1];
                e = {
                    marginLeft: m,
                    marginTop: r
                };
                a.each([ "left", "top" ], function(c, d) {
                    if (a.ui.pos[v[c]]) {
                        a.ui.pos[v[c]][d](z, {
                            targetWidth: n,
                            targetHeight: o,
                            elemWidth: h,
                            elemHeight: j,
                            collisionPosition: e,
                            collisionWidth: x,
                            collisionHeight: y,
                            offset: [ i[0] + A[0], i[1] + A[1] ],
                            my: b.my,
                            at: b.at,
                            within: t,
                            elem: g
                        });
                    }
                });
                if (b.using) {
                    f = function(a) {
                        var e = p.left - z.left, f = e + n - h, i = p.top - z.top, k = i + o - j, l = {
                            target: {
                                element: s,
                                left: p.left,
                                top: p.top,
                                width: n,
                                height: o
                            },
                            element: {
                                element: g,
                                left: z.left,
                                top: z.top,
                                width: h,
                                height: j
                            },
                            horizontal: f < 0 ? "left" : e > 0 ? "right" : "center",
                            vertical: k < 0 ? "top" : i > 0 ? "bottom" : "middle"
                        };
                        if (n < h && d(e + f) < n) {
                            l.horizontal = "center";
                        }
                        if (o < j && d(i + k) < o) {
                            l.vertical = "middle";
                        }
                        if (c(d(e), d(f)) > c(d(i), d(k))) {
                            l.important = "horizontal";
                        } else {
                            l.important = "vertical";
                        }
                        b.using.call(this, a, l);
                    };
                }
                g.offset(a.extend(z, {
                    using: f
                }));
            });
        };
        a.ui.pos = {
            _trigger: function(a, b, c, d) {
                if (b.elem) {
                    b.elem.trigger({
                        type: c,
                        position: a,
                        positionData: b,
                        triggered: d
                    });
                }
            },
            fit: {
                left: function(b, d) {
                    a.ui.pos._trigger(b, d, "posCollide", "fitLeft");
                    var e = d.within, f = e.isWindow ? e.scrollLeft : e.offset.left, g = e.width, h = b.left - d.collisionPosition.marginLeft, i = f - h, j = h + d.collisionWidth - g - f, k;
                    if (d.collisionWidth > g) {
                        if (i > 0 && j <= 0) {
                            k = b.left + i + d.collisionWidth - g - f;
                            b.left += i - k;
                        } else if (j > 0 && i <= 0) {
                            b.left = f;
                        } else {
                            if (i > j) {
                                b.left = f + g - d.collisionWidth;
                            } else {
                                b.left = f;
                            }
                        }
                    } else if (i > 0) {
                        b.left += i;
                    } else if (j > 0) {
                        b.left -= j;
                    } else {
                        b.left = c(b.left - h, b.left);
                    }
                    a.ui.pos._trigger(b, d, "posCollided", "fitLeft");
                },
                top: function(b, d) {
                    a.ui.pos._trigger(b, d, "posCollide", "fitTop");
                    var e = d.within, f = e.isWindow ? e.scrollTop : e.offset.top, g = d.within.height, h = b.top - d.collisionPosition.marginTop, i = f - h, j = h + d.collisionHeight - g - f, k;
                    if (d.collisionHeight > g) {
                        if (i > 0 && j <= 0) {
                            k = b.top + i + d.collisionHeight - g - f;
                            b.top += i - k;
                        } else if (j > 0 && i <= 0) {
                            b.top = f;
                        } else {
                            if (i > j) {
                                b.top = f + g - d.collisionHeight;
                            } else {
                                b.top = f;
                            }
                        }
                    } else if (i > 0) {
                        b.top += i;
                    } else if (j > 0) {
                        b.top -= j;
                    } else {
                        b.top = c(b.top - h, b.top);
                    }
                    a.ui.pos._trigger(b, d, "posCollided", "fitTop");
                }
            },
            flip: {
                left: function(b, c) {
                    a.ui.pos._trigger(b, c, "posCollide", "flipLeft");
                    var e = c.within, f = e.offset.left + e.scrollLeft, g = e.width, h = e.isWindow ? e.scrollLeft : e.offset.left, i = b.left - c.collisionPosition.marginLeft, j = i - h, k = i + c.collisionWidth - g - h, l = c.my[0] === "left" ? -c.elemWidth : c.my[0] === "right" ? c.elemWidth : 0, m = c.at[0] === "left" ? c.targetWidth : c.at[0] === "right" ? -c.targetWidth : 0, n = -2 * c.offset[0], o, p;
                    if (j < 0) {
                        o = b.left + l + m + n + c.collisionWidth - g - f;
                        if (o < 0 || o < d(j)) {
                            b.left += l + m + n;
                        }
                    } else if (k > 0) {
                        p = b.left - c.collisionPosition.marginLeft + l + m + n - h;
                        if (p > 0 || d(p) < k) {
                            b.left += l + m + n;
                        }
                    }
                    a.ui.pos._trigger(b, c, "posCollided", "flipLeft");
                },
                top: function(b, c) {
                    a.ui.pos._trigger(b, c, "posCollide", "flipTop");
                    var e = c.within, f = e.offset.top + e.scrollTop, g = e.height, h = e.isWindow ? e.scrollTop : e.offset.top, i = b.top - c.collisionPosition.marginTop, j = i - h, k = i + c.collisionHeight - g - h, l = c.my[1] === "top", m = l ? -c.elemHeight : c.my[1] === "bottom" ? c.elemHeight : 0, n = c.at[1] === "top" ? c.targetHeight : c.at[1] === "bottom" ? -c.targetHeight : 0, o = -2 * c.offset[1], p, q;
                    if (j < 0) {
                        q = b.top + m + n + o + c.collisionHeight - g - f;
                        if (q < 0 || q < d(j)) {
                            b.top += m + n + o;
                        }
                    } else if (k > 0) {
                        p = b.top - c.collisionPosition.marginTop + m + n + o - h;
                        if (p > 0 || d(p) < k) {
                            b.top += m + n + o;
                        }
                    }
                    a.ui.pos._trigger(b, c, "posCollided", "flipTop");
                }
            },
            flipfit: {
                left: function() {
                    a.ui.pos.flip.left.apply(this, arguments);
                    a.ui.pos.fit.left.apply(this, arguments);
                },
                top: function() {
                    a.ui.pos.flip.top.apply(this, arguments);
                    a.ui.pos.fit.top.apply(this, arguments);
                }
            }
        };
        (function() {
            var b, c, d, e, f, g = document.getElementsByTagName("body")[0], h = document.createElement("div");
            b = document.createElement(g ? "div" : "body");
            d = {
                visibility: "hidden",
                width: 0,
                height: 0,
                border: 0,
                margin: 0,
                background: "none"
            };
            if (g) {
                a.extend(d, {
                    position: "absolute",
                    left: "-1000px",
                    top: "-1000px"
                });
            }
            for (f in d) {
                b.style[f] = d[f];
            }
            b.appendChild(h);
            c = g || document.documentElement;
            c.insertBefore(b, c.firstChild);
            h.style.cssText = "position: absolute; left: 10.7432222px;";
            e = a(h).offset().left;
            a.support.offsetFractions = e > 10 && e < 11;
            b.innerHTML = "";
            c.removeChild(b);
        })();
    })();
    var c = a.ui.position;
});

(function(a) {
    "use strict";
    if (typeof define === "function" && define.amd) {
        define([ "jquery" ], a);
    } else if (window.jQuery && !window.jQuery.fn.iconpicker) {
        a(window.jQuery);
    }
})(function(a) {
    "use strict";
    var b = {
        isEmpty: function(a) {
            return a === false || a === "" || a === null || a === undefined;
        },
        isEmptyObject: function(a) {
            return this.isEmpty(a) === true || a.length === 0;
        },
        isElement: function(b) {
            return a(b).length > 0;
        },
        isString: function(a) {
            return typeof a === "string" || a instanceof String;
        },
        isArray: function(b) {
            return a.isArray(b);
        },
        inArray: function(b, c) {
            return a.inArray(b, c) !== -1;
        },
        throwError: function(a) {
            throw "Font Awesome Icon Picker Exception: " + a;
        }
    };
    var c = function(d, e) {
        this._id = c._idCounter++;
        this.element = a(d).addClass("iconpicker-element");
        this._trigger("iconpickerCreate", {
            iconpickerValue: this.iconpickerValue
        });
        this.options = a.extend({}, c.defaultOptions, this.element.data(), e);
        this.options.templates = a.extend({}, c.defaultOptions.templates, this.options.templates);
        this.options.originalPlacement = this.options.placement;
        this.container = b.isElement(this.options.container) ? a(this.options.container) : false;
        if (this.container === false) {
            if (this.element.is(".dropdown-toggle")) {
                this.container = a("~ .dropdown-menu:first", this.element);
            } else {
                this.container = this.element.is("input,textarea,button,.btn") ? this.element.parent() : this.element;
            }
        }
        this.container.addClass("iconpicker-container");
        if (this.isDropdownMenu()) {
            this.options.placement = "inline";
        }
        this.input = this.element.is("input,textarea") ? this.element.addClass("iconpicker-input") : false;
        if (this.input === false) {
            this.input = this.container.find(this.options.input);
            if (!this.input.is("input,textarea")) {
                this.input = false;
            }
        }
        this.component = this.isDropdownMenu() ? this.container.parent().find(this.options.component) : this.container.find(this.options.component);
        if (this.component.length === 0) {
            this.component = false;
        } else {
            this.component.find("i").addClass("iconpicker-component");
        }
        this._createPopover();
        this._createIconpicker();
        if (this.getAcceptButton().length === 0) {
            this.options.mustAccept = false;
        }
        if (this.isInputGroup()) {
            this.container.parent().append(this.popover);
        } else {
            this.container.append(this.popover);
        }
        this._bindElementEvents();
        this._bindWindowEvents();
        this.update(this.options.selected);
        if (this.isInline()) {
            this.show();
        }
        this._trigger("iconpickerCreated", {
            iconpickerValue: this.iconpickerValue
        });
    };
    c._idCounter = 0;
    c.defaultOptions = {
        title: false,
        selected: false,
        defaultValue: false,
        placement: "bottom",
        collision: "none",
        animation: true,
        hideOnSelect: false,
        showFooter: false,
        searchInFooter: false,
        mustAccept: false,
        selectedCustomClass: "bg-primary",
        icons: [],
        fullClassFormatter: function(a) {
            return a;
        },
        input: "input,.iconpicker-input",
        inputSearch: false,
        container: false,
        component: ".input-group-addon,.iconpicker-component",
        templates: {
            popover: '<div class="iconpicker-popover popover"><div class="arrow"></div>' + '<div class="popover-title"></div><div class="popover-content"></div></div>',
            footer: '<div class="popover-footer"></div>',
            buttons: '<button class="iconpicker-btn iconpicker-btn-cancel btn btn-default btn-sm">Cancel</button>' + ' <button class="iconpicker-btn iconpicker-btn-accept btn btn-primary btn-sm">Accept</button>',
            search: '<input type="search" class="form-control iconpicker-search" placeholder="Type to filter" />',
            iconpicker: '<div class="iconpicker"><div class="iconpicker-items"></div></div>',
            iconpickerItem: '<a role="button" href="#" class="iconpicker-item"><i></i></a>'
        }
    };
    c.batch = function(b, c) {
        var d = Array.prototype.slice.call(arguments, 2);
        return a(b).each(function() {
            var b = a(this).data("iconpicker");
            if (!!b) {
                b[c].apply(b, d);
            }
        });
    };
    c.prototype = {
        constructor: c,
        options: {},
        _id: 0,
        _trigger: function(b, c) {
            c = c || {};
            this.element.trigger(a.extend({
                type: b,
                iconpickerInstance: this
            }, c));
        },
        _createPopover: function() {
            this.popover = a(this.options.templates.popover);
            var c = this.popover.find(".popover-title");
            if (!!this.options.title) {
                c.append(a('<div class="popover-title-text">' + this.options.title + "</div>"));
            }
            if (this.hasSeparatedSearchInput() && !this.options.searchInFooter) {
                c.append(this.options.templates.search);
            } else if (!this.options.title) {
                c.remove();
            }
            if (this.options.showFooter && !b.isEmpty(this.options.templates.footer)) {
                var d = a(this.options.templates.footer);
                if (this.hasSeparatedSearchInput() && this.options.searchInFooter) {
                    d.append(a(this.options.templates.search));
                }
                if (!b.isEmpty(this.options.templates.buttons)) {
                    d.append(a(this.options.templates.buttons));
                }
                this.popover.append(d);
            }
            if (this.options.animation === true) {
                this.popover.addClass("fade");
            }
            return this.popover;
        },
        _createIconpicker: function() {
            var b = this;
            this.iconpicker = a(this.options.templates.iconpicker);
            var c = function(c) {
                var d = a(this);
                if (d.is("i")) {
                    d = d.parent();
                }
                b._trigger("iconpickerSelect", {
                    iconpickerItem: d,
                    iconpickerValue: b.iconpickerValue
                });
                if (b.options.mustAccept === false) {
                    b.update(d.data("iconpickerValue"));
                    b._trigger("iconpickerSelected", {
                        iconpickerItem: this,
                        iconpickerValue: b.iconpickerValue
                    });
                } else {
                    b.update(d.data("iconpickerValue"), true);
                }
                if (b.options.hideOnSelect && b.options.mustAccept === false) {
                    b.hide();
                }
            };
            for (var d in this.options.icons) {
                if (typeof this.options.icons[d].title === "string") {
                    var e = a(this.options.templates.iconpickerItem);
                    e.find("i").addClass(this.options.fullClassFormatter(this.options.icons[d].title));
                    e.data("iconpickerValue", this.options.icons[d].title).on("click.iconpicker", c);
                    this.iconpicker.find(".iconpicker-items").append(e.attr("title", "." + this.options.icons[d].title));
                    if (this.options.icons[d].searchTerms.length > 0) {
                        var f = "";
                        for (var g = 0; g < this.options.icons[d].searchTerms.length; g++) {
                            f = f + this.options.icons[d].searchTerms[g] + " ";
                        }
                        this.iconpicker.find(".iconpicker-items").append(e.attr("data-search-terms", f));
                    }
                }
            }
            this.popover.find(".popover-content").append(this.iconpicker);
            return this.iconpicker;
        },
        _isEventInsideIconpicker: function(b) {
            var c = a(b.target);
            if ((!c.hasClass("iconpicker-element") || c.hasClass("iconpicker-element") && !c.is(this.element)) && c.parents(".iconpicker-popover").length === 0) {
                return false;
            }
            return true;
        },
        _bindElementEvents: function() {
            var c = this;
            this.getSearchInput().on("keyup.iconpicker", function() {
                c.filter(a(this).val().toLowerCase());
            });
            this.getAcceptButton().on("click.iconpicker", function() {
                var a = c.iconpicker.find(".iconpicker-selected").get(0);
                c.update(c.iconpickerValue);
                c._trigger("iconpickerSelected", {
                    iconpickerItem: a,
                    iconpickerValue: c.iconpickerValue
                });
                if (!c.isInline()) {
                    c.hide();
                }
            });
            this.getCancelButton().on("click.iconpicker", function() {
                if (!c.isInline()) {
                    c.hide();
                }
            });
            this.element.on("focus.iconpicker", function(a) {
                c.show();
                a.stopPropagation();
            });
            if (this.hasComponent()) {
                this.component.on("click.iconpicker", function() {
                    c.toggle();
                });
            }
            if (this.hasInput()) {
                this.input.on("keyup.iconpicker", function(d) {
                    if (!b.inArray(d.keyCode, [ 38, 40, 37, 39, 16, 17, 18, 9, 8, 91, 93, 20, 46, 186, 190, 46, 78, 188, 44, 86 ])) {
                        c.update();
                    } else {
                        c._updateFormGroupStatus(c.getValid(this.value) !== false);
                    }
                    if (c.options.inputSearch === true) {
                        c.filter(a(this).val().toLowerCase());
                    }
                });
            }
        },
        _bindWindowEvents: function() {
            var b = a(window.document);
            var c = this;
            var d = ".iconpicker.inst" + this._id;
            a(window).on("resize.iconpicker" + d + " orientationchange.iconpicker" + d, function(a) {
                if (c.popover.hasClass("in")) {
                    c.updatePlacement();
                }
            });
            if (!c.isInline()) {
                b.on("mouseup" + d, function(a) {
                    if (!c._isEventInsideIconpicker(a) && !c.isInline()) {
                        c.hide();
                    }
                });
            }
        },
        _unbindElementEvents: function() {
            this.popover.off(".iconpicker");
            this.element.off(".iconpicker");
            if (this.hasInput()) {
                this.input.off(".iconpicker");
            }
            if (this.hasComponent()) {
                this.component.off(".iconpicker");
            }
            if (this.hasContainer()) {
                this.container.off(".iconpicker");
            }
        },
        _unbindWindowEvents: function() {
            a(window).off(".iconpicker.inst" + this._id);
            a(window.document).off(".iconpicker.inst" + this._id);
        },
        updatePlacement: function(b, c) {
            b = b || this.options.placement;
            this.options.placement = b;
            c = c || this.options.collision;
            c = c === true ? "flip" : c;
            var d = {
                at: "right bottom",
                my: "right top",
                of: this.hasInput() && !this.isInputGroup() ? this.input : this.container,
                collision: c === true ? "flip" : c,
                within: window
            };
            this.popover.removeClass("inline topLeftCorner topLeft top topRight topRightCorner " + "rightTop right rightBottom bottomRight bottomRightCorner " + "bottom bottomLeft bottomLeftCorner leftBottom left leftTop");
            if (typeof b === "object") {
                return this.popover.pos(a.extend({}, d, b));
            }
            switch (b) {
              case "inline":
                {
                    d = false;
                }
                break;

              case "topLeftCorner":
                {
                    d.my = "right bottom";
                    d.at = "left top";
                }
                break;

              case "topLeft":
                {
                    d.my = "left bottom";
                    d.at = "left top";
                }
                break;

              case "top":
                {
                    d.my = "center bottom";
                    d.at = "center top";
                }
                break;

              case "topRight":
                {
                    d.my = "right bottom";
                    d.at = "right top";
                }
                break;

              case "topRightCorner":
                {
                    d.my = "left bottom";
                    d.at = "right top";
                }
                break;

              case "rightTop":
                {
                    d.my = "left bottom";
                    d.at = "right center";
                }
                break;

              case "right":
                {
                    d.my = "left center";
                    d.at = "right center";
                }
                break;

              case "rightBottom":
                {
                    d.my = "left top";
                    d.at = "right center";
                }
                break;

              case "bottomRightCorner":
                {
                    d.my = "left top";
                    d.at = "right bottom";
                }
                break;

              case "bottomRight":
                {
                    d.my = "right top";
                    d.at = "right bottom";
                }
                break;

              case "bottom":
                {
                    d.my = "center top";
                    d.at = "center bottom";
                }
                break;

              case "bottomLeft":
                {
                    d.my = "left top";
                    d.at = "left bottom";
                }
                break;

              case "bottomLeftCorner":
                {
                    d.my = "right top";
                    d.at = "left bottom";
                }
                break;

              case "leftBottom":
                {
                    d.my = "right top";
                    d.at = "left center";
                }
                break;

              case "left":
                {
                    d.my = "right center";
                    d.at = "left center";
                }
                break;

              case "leftTop":
                {
                    d.my = "right bottom";
                    d.at = "left center";
                }
                break;

              default:
                {
                    return false;
                }
                break;
            }
            this.popover.css({
                display: this.options.placement === "inline" ? "" : "block"
            });
            if (d !== false) {
                this.popover.pos(d).css("maxWidth", a(window).width() - this.container.offset().left - 5);
            } else {
                this.popover.css({
                    top: "auto",
                    right: "auto",
                    bottom: "auto",
                    left: "auto",
                    maxWidth: "none"
                });
            }
            this.popover.addClass(this.options.placement);
            return true;
        },
        _updateComponents: function() {
            this.iconpicker.find(".iconpicker-item.iconpicker-selected").removeClass("iconpicker-selected " + this.options.selectedCustomClass);
            if (this.iconpickerValue) {
                this.iconpicker.find("." + this.options.fullClassFormatter(this.iconpickerValue).replace(/ /g, ".")).parent().addClass("iconpicker-selected " + this.options.selectedCustomClass);
            }
            if (this.hasComponent()) {
                var a = this.component.find("i");
                if (a.length > 0) {
                    a.attr("class", this.options.fullClassFormatter(this.iconpickerValue));
                } else {
                    this.component.html(this.getHtml());
                }
            }
        },
        _updateFormGroupStatus: function(a) {
            if (this.hasInput()) {
                if (a !== false) {
                    this.input.parents(".form-group:first").removeClass("has-error");
                } else {
                    this.input.parents(".form-group:first").addClass("has-error");
                }
                return true;
            }
            return false;
        },
        getValid: function(c) {
            if (!b.isString(c)) {
                c = "";
            }
            var d = c === "";
            c = a.trim(c);
            var e = false;
            for (var f = 0; f < this.options.icons.length; f++) {
                if (this.options.icons[f].title === c) {
                    e = true;
                    break;
                }
            }
            if (e || d) {
                return c;
            }
            return false;
        },
        setValue: function(a) {
            var b = this.getValid(a);
            if (b !== false) {
                this.iconpickerValue = b;
                this._trigger("iconpickerSetValue", {
                    iconpickerValue: b
                });
                return this.iconpickerValue;
            } else {
                this._trigger("iconpickerInvalid", {
                    iconpickerValue: a
                });
                return false;
            }
        },
        getHtml: function() {
            return '<i class="' + this.options.fullClassFormatter(this.iconpickerValue) + '"></i>';
        },
        setSourceValue: function(a) {
            a = this.setValue(a);
            if (a !== false && a !== "") {
                if (this.hasInput()) {
                    this.input.val(this.iconpickerValue);
                } else {
                    this.element.data("iconpickerValue", this.iconpickerValue);
                }
                this._trigger("iconpickerSetSourceValue", {
                    iconpickerValue: a
                });
            }
            return a;
        },
        getSourceValue: function(a) {
            a = a || this.options.defaultValue;
            var b = a;
            if (this.hasInput()) {
                b = this.input.val();
            } else {
                b = this.element.data("iconpickerValue");
            }
            if (b === undefined || b === "" || b === null || b === false) {
                b = a;
            }
            return b;
        },
        hasInput: function() {
            return this.input !== false;
        },
        isInputSearch: function() {
            return this.hasInput() && this.options.inputSearch === true;
        },
        isInputGroup: function() {
            return this.container.is(".input-group");
        },
        isDropdownMenu: function() {
            return this.container.is(".dropdown-menu");
        },
        hasSeparatedSearchInput: function() {
            return this.options.templates.search !== false && !this.isInputSearch();
        },
        hasComponent: function() {
            return this.component !== false;
        },
        hasContainer: function() {
            return this.container !== false;
        },
        getAcceptButton: function() {
            return this.popover.find(".iconpicker-btn-accept");
        },
        getCancelButton: function() {
            return this.popover.find(".iconpicker-btn-cancel");
        },
        getSearchInput: function() {
            return this.popover.find(".iconpicker-search");
        },
        filter: function(c) {
            if (b.isEmpty(c)) {
                this.iconpicker.find(".iconpicker-item").show();
                return a(false);
            } else {
                var d = [];
                this.iconpicker.find(".iconpicker-item").each(function() {
                    var b = a(this);
                    var e = b.attr("title").toLowerCase();
                    var f = b.attr("data-search-terms") ? b.attr("data-search-terms").toLowerCase() : "";
                    e = e + " " + f;
                    var g = false;
                    try {
                        g = new RegExp("(^|\\W)" + c, "g");
                    } catch (a) {
                        g = false;
                    }
                    if (g !== false && e.match(g)) {
                        d.push(b);
                        b.show();
                    } else {
                        b.hide();
                    }
                });
                return d;
            }
        },
        show: function() {
            if (this.popover.hasClass("in")) {
                return false;
            }
            a.iconpicker.batch(a(".iconpicker-popover.in:not(.inline)").not(this.popover), "hide");
            this._trigger("iconpickerShow", {
                iconpickerValue: this.iconpickerValue
            });
            this.updatePlacement();
            this.popover.addClass("in");
            setTimeout(a.proxy(function() {
                this.popover.css("display", this.isInline() ? "" : "block");
                this._trigger("iconpickerShown", {
                    iconpickerValue: this.iconpickerValue
                });
            }, this), this.options.animation ? 300 : 1);
        },
        hide: function() {
            if (!this.popover.hasClass("in")) {
                return false;
            }
            this._trigger("iconpickerHide", {
                iconpickerValue: this.iconpickerValue
            });
            this.popover.removeClass("in");
            setTimeout(a.proxy(function() {
                this.popover.css("display", "none");
                this.getSearchInput().val("");
                this.filter("");
                this._trigger("iconpickerHidden", {
                    iconpickerValue: this.iconpickerValue
                });
            }, this), this.options.animation ? 300 : 1);
        },
        toggle: function() {
            if (this.popover.is(":visible")) {
                this.hide();
            } else {
                this.show(true);
            }
        },
        update: function(a, b) {
            a = a ? a : this.getSourceValue(this.iconpickerValue);
            this._trigger("iconpickerUpdate", {
                iconpickerValue: this.iconpickerValue
            });
            if (b === true) {
                a = this.setValue(a);
            } else {
                a = this.setSourceValue(a);
                this._updateFormGroupStatus(a !== false);
            }
            if (a !== false) {
                this._updateComponents();
            }
            this._trigger("iconpickerUpdated", {
                iconpickerValue: this.iconpickerValue
            });
            return a;
        },
        destroy: function() {
            this._trigger("iconpickerDestroy", {
                iconpickerValue: this.iconpickerValue
            });
            this.element.removeData("iconpicker").removeData("iconpickerValue").removeClass("iconpicker-element");
            this._unbindElementEvents();
            this._unbindWindowEvents();
            a(this.popover).remove();
            this._trigger("iconpickerDestroyed", {
                iconpickerValue: this.iconpickerValue
            });
        },
        disable: function() {
            if (this.hasInput()) {
                this.input.prop("disabled", true);
                return true;
            }
            return false;
        },
        enable: function() {
            if (this.hasInput()) {
                this.input.prop("disabled", false);
                return true;
            }
            return false;
        },
        isDisabled: function() {
            if (this.hasInput()) {
                return this.input.prop("disabled") === true;
            }
            return false;
        },
        isInline: function() {
            return this.options.placement === "inline" || this.popover.hasClass("inline");
        }
    };
    a.iconpicker = c;
    a.fn.iconpicker = function(b) {
        return this.each(function() {
            var d = a(this);
            if (!d.data("iconpicker")) {
                d.data("iconpicker", new c(this, typeof b === "object" ? b : {}));
            }
        });
    };
    c.defaultOptions = a.extend(c.defaultOptions, {
        icons: [ {
	title: "fa fa-glass-martini",
	searchTerms: []
},
{
	title: "fa fa-music",
	searchTerms: []
},
{
	title: "fa fa-search",
	searchTerms: []
},
{
	title: "fa fa-envelope",
	searchTerms: []
},
{
	title: "fa fa-heart",
	searchTerms: []
},
{
	title: "fa fa-star",
	searchTerms: []
},
{
	title: "fa fa-star-o",
	searchTerms: []
},
{
	title: "fa fa-user",
	searchTerms: []
},
{
	title: "fa fa-film",
	searchTerms: []
},
{
	title: "fa fa-th-large",
	searchTerms: []
},
{
	title: "fa fa-th",
	searchTerms: []
},
{
	title: "fa fa-th-list",
	searchTerms: []
},
{
	title: "fa fa-check",
	searchTerms: []
},
{
	title: "fa fa-times",
	searchTerms: []
},
{
	title: "fa fa-search-plus",
	searchTerms: []
},
{
	title: "fa fa-search-minus",
	searchTerms: []
},
{
	title: "fa fa-power-off",
	searchTerms: []
},
{
	title: "fa fa-signal",
	searchTerms: []
},
{
	title: "fa fa-cog",
	searchTerms: []
},
{
	title: "fa fa-trash-o",
	searchTerms: []
},
{
	title: "fa fa-home",
	searchTerms: []
},
{
	title: "fa fa-file-o",
	searchTerms: []
},
{
	title: "fa fa-clock-o",
	searchTerms: []
},
{
	title: "fa fa-road",
	searchTerms: []
},
{
	title: "fa fa-download",
	searchTerms: []
},
{
	title: "fa fa-arrow-circle-o-down",
	searchTerms: []
},
{
	title: "fa fa-arrow-circle-o-up",
	searchTerms: []
},
{
	title: "fa fa-inbox",
	searchTerms: []
},
{
	title: "fa fa-play-circle-o",
	searchTerms: []
},
{
	title: "fa fa-repeat",
	searchTerms: []
},
{
	title: "fa fa-refresh",
	searchTerms: []
},
{
	title: "fa fa-list-alt",
	searchTerms: []
},
{
	title: "fa fa-lock",
	searchTerms: []
},
{
	title: "fa fa-flag",
	searchTerms: []
},
{
	title: "fa fa-headphones",
	searchTerms: []
},
{
	title: "fa fa-volume-off",
	searchTerms: []
},
{
	title: "fa fa-volume-down",
	searchTerms: []
},
{
	title: "fa fa-volume-up",
	searchTerms: []
},
{
	title: "fa fa-qrcode",
	searchTerms: []
},
{
	title: "fa fa-barcode",
	searchTerms: []
},
{
	title: "fa fa-tag",
	searchTerms: []
},
{
	title: "fa fa-tags",
	searchTerms: []
},
{
	title: "fa fa-book",
	searchTerms: []
},
{
	title: "fa fa-bookmark",
	searchTerms: []
},
{
	title: "fa fa-print",
	searchTerms: []
},
{
	title: "fa fa-camera",
	searchTerms: []
},
{
	title: "fa fa-font",
	searchTerms: []
},
{
	title: "fa fa-bold",
	searchTerms: []
},
{
	title: "fa fa-italic",
	searchTerms: []
},
{
	title: "fa fa-text-height",
	searchTerms: []
},
{
	title: "fa fa-text-width",
	searchTerms: []
},
{
	title: "fa fa-align-left",
	searchTerms: []
},
{
	title: "fa fa-align-center",
	searchTerms: []
},
{
	title: "fa fa-align-right",
	searchTerms: []
},
{
	title: "fa fa-align-justify",
	searchTerms: []
},
{
	title: "fa fa-list",
	searchTerms: []
},
{
	title: "fa fa-outdent",
	searchTerms: []
},
{
	title: "fa fa-indent",
	searchTerms: []
},
{
	title: "fa fa-video-camera",
	searchTerms: []
},
{
	title: "fa fa-picture-o",
	searchTerms: []
},
{
	title: "fa fa-pencil",
	searchTerms: []
},
{
	title: "fa fa-map-marker",
	searchTerms: []
},
{
	title: "fa fa-adjust",
	searchTerms: []
},
{
	title: "fa fa-tint",
	searchTerms: []
},
{
	title: "fa fa-pencil-square-o",
	searchTerms: []
},
{
	title: "fa fa-share-square-o",
	searchTerms: []
},
{
	title: "fa fa-check-square-o",
	searchTerms: []
},
{
	title: "fa fa-arrows",
	searchTerms: []
},
{
	title: "fa fa-step-backward",
	searchTerms: []
},
{
	title: "fa fa-fast-backward",
	searchTerms: []
},
{
	title: "fa fa-backward",
	searchTerms: []
},
{
	title: "fa fa-play",
	searchTerms: []
},
{
	title: "fa fa-pause",
	searchTerms: []
},
{
	title: "fa fa-stop",
	searchTerms: []
},
{
	title: "fa fa-forward",
	searchTerms: []
},
{
	title: "fa fa-fast-forward",
	searchTerms: []
},
{
	title: "fa fa-step-forward",
	searchTerms: []
},
{
	title: "fa fa-eject",
	searchTerms: []
},
{
	title: "fa fa-chevron-left",
	searchTerms: []
},
{
	title: "fa fa-chevron-right",
	searchTerms: []
},
{
	title: "fa fa-plus-circle",
	searchTerms: []
},
{
	title: "fa fa-minus-circle",
	searchTerms: []
},
{
	title: "fa fa-times-circle",
	searchTerms: []
},
{
	title: "fa fa-check-circle",
	searchTerms: []
},
{
	title: "fa fa-question-circle",
	searchTerms: []
},
{
	title: "fa fa-info-circle",
	searchTerms: []
},
{
	title: "fa fa-crosshairs",
	searchTerms: []
},
{
	title: "fa fa-times-circle-o",
	searchTerms: []
},
{
	title: "fa fa-check-circle-o",
	searchTerms: []
},
{
	title: "fa fa-ban",
	searchTerms: []
},
{
	title: "fa fa-arrow-left",
	searchTerms: []
},
{
	title: "fa fa-arrow-right",
	searchTerms: []
},
{
	title: "fa fa-arrow-up",
	searchTerms: []
},
{
	title: "fa fa-arrow-down",
	searchTerms: []
},
{
	title: "fa fa-share",
	searchTerms: []
},
{
	title: "fa fa-expand",
	searchTerms: []
},
{
	title: "fa fa-compress",
	searchTerms: []
},
{
	title: "fa fa-plus",
	searchTerms: []
},
{
	title: "fa fa-minus",
	searchTerms: []
},
{
	title: "fa fa-asterisk",
	searchTerms: []
},
{
	title: "fa fa-exclamation-circle",
	searchTerms: []
},
{
	title: "fa fa-gift",
	searchTerms: []
},
{
	title: "fa fa-leaf",
	searchTerms: []
},
{
	title: "fa fa-fire",
	searchTerms: []
},
{
	title: "fa fa-eye",
	searchTerms: []
},
{
	title: "fa fa-eye-slash",
	searchTerms: []
},
{
	title: "fa fa-exclamation-triangle",
	searchTerms: []
},
{
	title: "fa fa-plane",
	searchTerms: []
},
{
	title: "fa fa-calendar",
	searchTerms: []
},
{
	title: "fa fa-random",
	searchTerms: []
},
{
	title: "fa fa-comment",
	searchTerms: []
},
{
	title: "fa fa-magnet",
	searchTerms: []
},
{
	title: "fa fa-chevron-up",
	searchTerms: []
},
{
	title: "fa fa-chevron-down",
	searchTerms: []
},
{
	title: "fa fa-retweet",
	searchTerms: []
},
{
	title: "fa fa-shopping-cart",
	searchTerms: []
},
{
	title: "fa fa-folder",
	searchTerms: []
},
{
	title: "fa fa-folder-open",
	searchTerms: []
},
{
	title: "fa fa-arrows-v",
	searchTerms: []
},
{
	title: "fa fa-arrows-h",
	searchTerms: []
},
{
	title: "fa fa-bar-chart",
	searchTerms: []
},
{
	title: "fa fa-twitter-square",
	searchTerms: []
},
{
	title: "fa fa-facebook-square",
	searchTerms: []
},
{
	title: "fa fa-camera-retro",
	searchTerms: []
},
{
	title: "fa fa-key",
	searchTerms: []
},
{
	title: "fa fa-cogs",
	searchTerms: []
},
{
	title: "fa fa-comments",
	searchTerms: []
},
{
	title: "fa fa-thumbs-o-up",
	searchTerms: []
},
{
	title: "fa fa-thumbs-o-down",
	searchTerms: []
},
{
	title: "fa fa-star-half",
	searchTerms: []
},
{
	title: "fa fa-heart-o",
	searchTerms: []
},
{
	title: "fa fa-sign-out",
	searchTerms: []
},
{
	title: "fa fa-linkedin-square",
	searchTerms: []
},
{
	title: "fa fa-thumb-tack",
	searchTerms: []
},
{
	title: "fa fa-external-link",
	searchTerms: []
},
{
	title: "fa fa-sign-in",
	searchTerms: []
},
{
	title: "fa fa-trophy",
	searchTerms: []
},
{
	title: "fa fa-github-square",
	searchTerms: []
},
{
	title: "fa fa-upload",
	searchTerms: []
},
{
	title: "fa fa-lemon-o",
	searchTerms: []
},
{
	title: "fa fa-phone",
	searchTerms: []
},
{
	title: "fa fa-square-o",
	searchTerms: []
},
{
	title: "fa fa-bookmark-o",
	searchTerms: []
},
{
	title: "fa fa-phone-square",
	searchTerms: []
},
{
	title: "fa fa-twitter",
	searchTerms: []
},
{
	title: "fa fa-facebook",
	searchTerms: []
},
{
	title: "fa fa-github",
	searchTerms: []
},
{
	title: "fa fa-unlock",
	searchTerms: []
},
{
	title: "fa fa-credit-card",
	searchTerms: []
},
{
	title: "fa fa-rss",
	searchTerms: []
},
{
	title: "fa fa-hdd-o",
	searchTerms: []
},
{
	title: "fa fa-bullhorn",
	searchTerms: []
},
{
	title: "fa fa-bell",
	searchTerms: []
},
{
	title: "fa fa-certificate",
	searchTerms: []
},
{
	title: "fa fa-hand-o-right",
	searchTerms: []
},
{
	title: "fa fa-hand-o-left",
	searchTerms: []
},
{
	title: "fa fa-hand-o-up",
	searchTerms: []
},
{
	title: "fa fa-hand-o-down",
	searchTerms: []
},
{
	title: "fa fa-arrow-circle-left",
	searchTerms: []
},
{
	title: "fa fa-arrow-circle-right",
	searchTerms: []
},
{
	title: "fa fa-arrow-circle-up",
	searchTerms: []
},
{
	title: "fa fa-arrow-circle-down",
	searchTerms: []
},
{
	title: "fa fa-globe",
	searchTerms: []
},
{
	title: "fa fa-wrench",
	searchTerms: []
},
{
	title: "fa fa-tasks",
	searchTerms: []
},
{
	title: "fa fa-filter",
	searchTerms: []
},
{
	title: "fa fa-briefcase",
	searchTerms: []
},
{
	title: "fa fa-arrows-alt",
	searchTerms: []
},
{
	title: "fa fa-users",
	searchTerms: []
},
{
	title: "fa fa-link",
	searchTerms: []
},
{
	title: "fa fa-cloud",
	searchTerms: []
},
{
	title: "fa fa-flask",
	searchTerms: []
},
{
	title: "fa fa-scissors",
	searchTerms: []
},
{
	title: "fa fa-files-o",
	searchTerms: []
},
{
	title: "fa fa-paperclip",
	searchTerms: []
},
{
	title: "fa fa-floppy-o",
	searchTerms: []
},
{
	title: "fa fa-square",
	searchTerms: []
},
{
	title: "fa fa-bars",
	searchTerms: []
},
{
	title: "fa fa-list-ul",
	searchTerms: []
},
{
	title: "fa fa-list-ol",
	searchTerms: []
},
{
	title: "fa fa-strikethrough",
	searchTerms: []
},
{
	title: "fa fa-underline",
	searchTerms: []
},
{
	title: "fa fa-table",
	searchTerms: []
},
{
	title: "fa fa-magic",
	searchTerms: []
},
{
	title: "fa fa-truck",
	searchTerms: []
},
{
	title: "fa fa-pinterest",
	searchTerms: []
},
{
	title: "fa fa-pinterest-square",
	searchTerms: []
},
{
	title: "fa fa-google-plus-square",
	searchTerms: []
},
{
	title: "fa fa-google-plus",
	searchTerms: []
},
{
	title: "fa fa-money",
	searchTerms: []
},
{
	title: "fa fa-caret-down",
	searchTerms: []
},
{
	title: "fa fa-caret-up",
	searchTerms: []
},
{
	title: "fa fa-caret-left",
	searchTerms: []
},
{
	title: "fa fa-caret-right",
	searchTerms: []
},
{
	title: "fa fa-columns",
	searchTerms: []
},
{
	title: "fa fa-sort",
	searchTerms: []
},
{
	title: "fa fa-sort-desc",
	searchTerms: []
},
{
	title: "fa fa-sort-asc",
	searchTerms: []
},
{
	title: "fa fa-envelope",
	searchTerms: []
},
{
	title: "fa fa-linkedin",
	searchTerms: []
},
{
	title: "fa fa-undo",
	searchTerms: []
},
{
	title: "fa fa-gavel",
	searchTerms: []
},
{
	title: "fa fa-tachometer",
	searchTerms: []
},
{
	title: "fa fa-comment-o",
	searchTerms: []
},
{
	title: "fa fa-comments-o",
	searchTerms: []
},
{
	title: "fa fa-bolt",
	searchTerms: []
},
{
	title: "fa fa-sitemap",
	searchTerms: []
},
{
	title: "fa fa-umbrella",
	searchTerms: []
},
{
	title: "fa fa-clipboard",
	searchTerms: []
},
{
	title: "fa fa-lightbulb-o",
	searchTerms: []
},
{
	title: "fa fa-exchange",
	searchTerms: []
},
{
	title: "fa fa-cloud-download",
	searchTerms: []
},
{
	title: "fa fa-cloud-upload",
	searchTerms: []
},
{
	title: "fa fa-user-md",
	searchTerms: []
},
{
	title: "fa fa-stethoscope",
	searchTerms: []
},
{
	title: "fa fa-suitcase",
	searchTerms: []
},
{
	title: "fa fa-bell-o",
	searchTerms: []
},
{
	title: "fa fa-coffee",
	searchTerms: []
},
{
	title: "fa fa-cutlery",
	searchTerms: []
},
{
	title: "fa fa-file-text-o",
	searchTerms: []
},
{
	title: "fa fa-building-o",
	searchTerms: []
},
{
	title: "fa fa-hospital-o",
	searchTerms: []
},
{
	title: "fa fa-ambulance",
	searchTerms: []
},
{
	title: "fa fa-medkit",
	searchTerms: []
},
{
	title: "fa fa-fighter-jet",
	searchTerms: []
},
{
	title: "fa fa-beer",
	searchTerms: []
},
{
	title: "fa fa-h-square",
	searchTerms: []
},
{
	title: "fa fa-plus-square",
	searchTerms: []
},
{
	title: "fa fa-angle-double-left",
	searchTerms: []
},
{
	title: "fa fa-angle-double-right",
	searchTerms: []
},
{
	title: "fa fa-angle-double-up",
	searchTerms: []
},
{
	title: "fa fa-angle-double-down",
	searchTerms: []
},
{
	title: "fa fa-angle-left",
	searchTerms: []
},
{
	title: "fa fa-angle-right",
	searchTerms: []
},
{
	title: "fa fa-angle-up",
	searchTerms: []
},
{
	title: "fa fa-angle-down",
	searchTerms: []
},
{
	title: "fa fa-desktop",
	searchTerms: []
},
{
	title: "fa fa-laptop",
	searchTerms: []
},
{
	title: "fa fa-tablet",
	searchTerms: []
},
{
	title: "fa fa-mobile",
	searchTerms: []
},
{
	title: "fa fa-circle-o",
	searchTerms: []
},
{
	title: "fa fa-quote-left",
	searchTerms: []
},
{
	title: "fa fa-quote-right",
	searchTerms: []
},
{
	title: "fa fa-spinner",
	searchTerms: []
},
{
	title: "fa fa-circle",
	searchTerms: []
},
{
	title: "fa fa-reply",
	searchTerms: []
},
{
	title: "fa fa-github-alt",
	searchTerms: []
},
{
	title: "fa fa-folder-o",
	searchTerms: []
},
{
	title: "fa fa-folder-open-o",
	searchTerms: []
},
{
	title: "fa fa-smile-o",
	searchTerms: []
},
{
	title: "fa fa-frown-o",
	searchTerms: []
},
{
	title: "fa fa-meh-o",
	searchTerms: []
},
{
	title: "fa fa-gamepad",
	searchTerms: []
},
{
	title: "fa fa-keyboard-o",
	searchTerms: []
},
{
	title: "fa fa-flag-o",
	searchTerms: []
},
{
	title: "fa fa-flag-checkered",
	searchTerms: []
},
{
	title: "fa fa-terminal",
	searchTerms: []
},
{
	title: "fa fa-code",
	searchTerms: []
},
{
	title: "fa fa-reply-all",
	searchTerms: []
},
{
	title: "fa fa-star-half-o",
	searchTerms: []
},
{
	title: "fa fa-location-arrow",
	searchTerms: []
},
{
	title: "fa fa-crop",
	searchTerms: []
},
{
	title: "fa fa-code-fork",
	searchTerms: []
},
{
	title: "fa fa-chain-broken",
	searchTerms: []
},
{
	title: "fa fa-question",
	searchTerms: []
},
{
	title: "fa fa-info",
	searchTerms: []
},
{
	title: "fa fa-exclamation",
	searchTerms: []
},
{
	title: "fa fa-superscript",
	searchTerms: []
},
{
	title: "fa fa-subscript",
	searchTerms: []
},
{
	title: "fa fa-eraser",
	searchTerms: []
},
{
	title: "fa fa-puzzle-piece",
	searchTerms: []
},
{
	title: "fa fa-microphone",
	searchTerms: []
},
{
	title: "fa fa-microphone-slash",
	searchTerms: []
},
{
	title: "fa fa-shield",
	searchTerms: []
},
{
	title: "fa fa-calendar-o",
	searchTerms: []
},
{
	title: "fa fa-fire-extinguisher",
	searchTerms: []
},
{
	title: "fa fa-rocket",
	searchTerms: []
},
{
	title: "fa fa-maxcdn",
	searchTerms: []
},
{
	title: "fa fa-chevron-circle-left",
	searchTerms: []
},
{
	title: "fa fa-chevron-circle-right",
	searchTerms: []
},
{
	title: "fa fa-chevron-circle-up",
	searchTerms: []
},
{
	title: "fa fa-chevron-circle-down",
	searchTerms: []
},
{
	title: "fa fa-html5",
	searchTerms: []
},
{
	title: "fa fa-css3",
	searchTerms: []
},
{
	title: "fa fa-anchor",
	searchTerms: []
},
{
	title: "fa fa-unlock-alt",
	searchTerms: []
},
{
	title: "fa fa-bullseye",
	searchTerms: []
},
{
	title: "fa fa-ellipsis-h",
	searchTerms: []
},
{
	title: "fa fa-ellipsis-v",
	searchTerms: []
},
{
	title: "fa fa-rss-square",
	searchTerms: []
},
{
	title: "fa fa-play-circle",
	searchTerms: []
},
{
	title: "fa fa-ticket",
	searchTerms: []
},
{
	title: "fa fa-minus-square",
	searchTerms: []
},
{
	title: "fa fa-minus-square-o",
	searchTerms: []
},
{
	title: "fa fa-level-up",
	searchTerms: []
},
{
	title: "fa fa-level-down",
	searchTerms: []
},
{
	title: "fa fa-check-square",
	searchTerms: []
},
{
	title: "fa fa-pencil-square",
	searchTerms: []
},
{
	title: "fa fa-external-link-square",
	searchTerms: []
},
{
	title: "fa fa-share-square",
	searchTerms: []
},
{
	title: "fa fa-compass",
	searchTerms: []
},
{
	title: "fa fa-caret-square-o-down",
	searchTerms: []
},
{
	title: "fa fa-caret-square-o-up",
	searchTerms: []
},
{
	title: "fa fa-caret-square-o-right",
	searchTerms: []
},
{
	title: "fa fa-eur",
	searchTerms: []
},
{
	title: "fa fa-gbp",
	searchTerms: []
},
{
	title: "fa fa-usd",
	searchTerms: []
},
{
	title: "fa fa-inr",
	searchTerms: []
},
{
	title: "fa fa-jpy",
	searchTerms: []
},
{
	title: "fa fa-rub",
	searchTerms: []
},
{
	title: "fa fa-krw",
	searchTerms: []
},
{
	title: "fa fa-btc",
	searchTerms: []
},
{
	title: "fa fa-file",
	searchTerms: []
},
{
	title: "fa fa-file-text",
	searchTerms: []
},
{
	title: "fa fa-sort-alpha-asc",
	searchTerms: []
},
{
	title: "fa fa-sort-alpha-desc",
	searchTerms: []
},
{
	title: "fa fa-sort-amount-asc",
	searchTerms: []
},
{
	title: "fa fa-sort-amount-desc",
	searchTerms: []
},
{
	title: "fa fa-sort-numeric-asc",
	searchTerms: []
},
{
	title: "fa fa-sort-numeric-desc",
	searchTerms: []
},
{
	title: "fa fa-thumbs-up",
	searchTerms: []
},
{
	title: "fa fa-thumbs-down",
	searchTerms: []
},
{
	title: "fa fa-youtube-square",
	searchTerms: []
},
{
	title: "fa fa-youtube",
	searchTerms: []
},
{
	title: "fa fa-xing",
	searchTerms: []
},
{
	title: "fa fa-xing-square",
	searchTerms: []
},
{
	title: "fa fa-youtube-play",
	searchTerms: []
},
{
	title: "fa fa-dropbox",
	searchTerms: []
},
{
	title: "fa fa-stack-overflow",
	searchTerms: []
},
{
	title: "fa fa-instagram",
	searchTerms: []
},
{
	title: "fa fa-flickr",
	searchTerms: []
},
{
	title: "fa fa-adn",
	searchTerms: []
},
{
	title: "fa fa-bitbucket",
	searchTerms: []
},
{
	title: "fa fa-bitbucket-square",
	searchTerms: []
},
{
	title: "fa fa-tumblr",
	searchTerms: []
},
{
	title: "fa fa-tumblr-square",
	searchTerms: []
},
{
	title: "fa fa-long-arrow-down",
	searchTerms: []
},
{
	title: "fa fa-long-arrow-up",
	searchTerms: []
},
{
	title: "fa fa-long-arrow-left",
	searchTerms: []
},
{
	title: "fa fa-long-arrow-right",
	searchTerms: []
},
{
	title: "fa fa-apple",
	searchTerms: []
},
{
	title: "fa fa-windows",
	searchTerms: []
},
{
	title: "fa fa-android",
	searchTerms: []
},
{
	title: "fa fa-linux",
	searchTerms: []
},
{
	title: "fa fa-dribbble",
	searchTerms: []
},
{
	title: "fa fa-skype",
	searchTerms: []
},
{
	title: "fa fa-foursquare",
	searchTerms: []
},
{
	title: "fa fa-trello",
	searchTerms: []
},
{
	title: "fa fa-female",
	searchTerms: []
},
{
	title: "fa fa-male",
	searchTerms: []
},
{
	title: "fa fa-gratipay",
	searchTerms: []
},
{
	title: "fa fa-sun-o",
	searchTerms: []
},
{
	title: "fa fa-moon-o",
	searchTerms: []
},
{
	title: "fa fa-archive",
	searchTerms: []
},
{
	title: "fa fa-bug",
	searchTerms: []
},
{
	title: "fa fa-vk",
	searchTerms: []
},
{
	title: "fa fa-weibo",
	searchTerms: []
},
{
	title: "fa fa-renren",
	searchTerms: []
},
{
	title: "fa fa-pagelines",
	searchTerms: []
},
{
	title: "fa fa-stack-exchange",
	searchTerms: []
},
{
	title: "fa fa-arrow-circle-o-right",
	searchTerms: []
},
{
	title: "fa fa-arrow-circle-o-left",
	searchTerms: []
},
{
	title: "fa fa-caret-square-o-left",
	searchTerms: []
},
{
	title: "fa fa-dot-circle-o",
	searchTerms: []
},
{
	title: "fa fa-wheelchair",
	searchTerms: []
},
{
	title: "fa fa-vimeo-square",
	searchTerms: []
},
{
	title: "fa fa-try",
	searchTerms: []
},
{
	title: "fa fa-plus-square-o",
	searchTerms: []
},
{
	title: "fa fa-space-shuttle",
	searchTerms: []
},
{
	title: "fa fa-slack",
	searchTerms: []
},
{
	title: "fa fa-envelope-square",
	searchTerms: []
},
{
	title: "fa fa-wordpress",
	searchTerms: []
},
{
	title: "fa fa-openid",
	searchTerms: []
},
{
	title: "fa fa-university",
	searchTerms: []
},
{
	title: "fa fa-graduation-cap",
	searchTerms: []
},
{
	title: "fa fa-yahoo",
	searchTerms: []
},
{
	title: "fa fa-google",
	searchTerms: []
},
{
	title: "fa fa-reddit",
	searchTerms: []
},
{
	title: "fa fa-reddit-square",
	searchTerms: []
},
{
	title: "fa fa-stumbleupon-circle",
	searchTerms: []
},
{
	title: "fa fa-stumbleupon",
	searchTerms: []
},
{
	title: "fa fa-delicious",
	searchTerms: []
},
{
	title: "fa fa-digg",
	searchTerms: []
},
{
	title: "fa fa-pied-piper",
	searchTerms: []
},
{
	title: "fa fa-pied-piper-alt",
	searchTerms: []
},
{
	title: "fa fa-drupal",
	searchTerms: []
},
{
	title: "fa fa-joomla",
	searchTerms: []
},
{
	title: "fa fa-language",
	searchTerms: []
},
{
	title: "fa fa-fax",
	searchTerms: []
},
{
	title: "fa fa-building",
	searchTerms: []
},
{
	title: "fa fa-child",
	searchTerms: []
},
{
	title: "fa fa-paw",
	searchTerms: []
},
{
	title: "fa fa-spoon",
	searchTerms: []
},
{
	title: "fa fa-cube",
	searchTerms: []
},
{
	title: "fa fa-cubes",
	searchTerms: []
},
{
	title: "fa fa-behance",
	searchTerms: []
},
{
	title: "fa fa-behance-square",
	searchTerms: []
},
{
	title: "fa fa-steam",
	searchTerms: []
},
{
	title: "fa fa-steam-square",
	searchTerms: []
},
{
	title: "fa fa-recycle",
	searchTerms: []
},
{
	title: "fa fa-car",
	searchTerms: []
},
{
	title: "fa fa-taxi",
	searchTerms: []
},
{
	title: "fa fa-tree",
	searchTerms: []
},
{
	title: "fa fa-spotify",
	searchTerms: []
},
{
	title: "fa fa-deviantart",
	searchTerms: []
},
{
	title: "fa fa-soundcloud",
	searchTerms: []
},
{
	title: "fa fa-database",
	searchTerms: []
},
{
	title: "fa fa-file-pdf-o",
	searchTerms: []
},
{
	title: "fa fa-file-word-o",
	searchTerms: []
},
{
	title: "fa fa-file-excel-o",
	searchTerms: []
},
{
	title: "fa fa-file-powerpoint-o",
	searchTerms: []
},
{
	title: "fa fa-file-image-o",
	searchTerms: []
},
{
	title: "fa fa-file-archive-o",
	searchTerms: []
},
{
	title: "fa fa-file-audio-o",
	searchTerms: []
},
{
	title: "fa fa-file-video-o",
	searchTerms: []
},
{
	title: "fa fa-file-code-o",
	searchTerms: []
},
{
	title: "fa fa-vine",
	searchTerms: []
},
{
	title: "fa fa-codepen",
	searchTerms: []
},
{
	title: "fa fa-jsfiddle",
	searchTerms: []
},
{
	title: "fa fa-life-ring",
	searchTerms: []
},
{
	title: "fa fa-circle-o-notch",
	searchTerms: []
},
{
	title: "fa fa-rebel",
	searchTerms: []
},
{
	title: "fa fa-empire",
	searchTerms: []
},
{
	title: "fa fa-git-square",
	searchTerms: []
},
{
	title: "fa fa-git",
	searchTerms: []
},
{
	title: "fa fa-hacker-news",
	searchTerms: []
},
{
	title: "fa fa-tencent-weibo",
	searchTerms: []
},
{
	title: "fa fa-qq",
	searchTerms: []
},
{
	title: "fa fa-weixin",
	searchTerms: []
},
{
	title: "fa fa-paper-plane",
	searchTerms: []
},
{
	title: "fa fa-paper-plane-o",
	searchTerms: []
},
{
	title: "fa fa-history",
	searchTerms: []
},
{
	title: "fa fa-circle-thin",
	searchTerms: []
},
{
	title: "fa fa-header",
	searchTerms: []
},
{
	title: "fa fa-paragraph",
	searchTerms: []
},
{
	title: "fa fa-sliders",
	searchTerms: []
},
{
	title: "fa fa-share-alt",
	searchTerms: []
},
{
	title: "fa fa-share-alt-square",
	searchTerms: []
},
{
	title: "fa fa-bomb",
	searchTerms: []
},
{
	title: "fa fa-futbol-o",
	searchTerms: []
},
{
	title: "fa fa-tty",
	searchTerms: []
},
{
	title: "fa fa-binoculars",
	searchTerms: []
},
{
	title: "fa fa-plug",
	searchTerms: []
},
{
	title: "fa fa-slideshare",
	searchTerms: []
},
{
	title: "fa fa-twitch",
	searchTerms: []
},
{
	title: "fa fa-yelp",
	searchTerms: []
},
{
	title: "fa fa-newspaper-o",
	searchTerms: []
},
{
	title: "fa fa-wifi",
	searchTerms: []
},
{
	title: "fa fa-calculator",
	searchTerms: []
},
{
	title: "fa fa-paypal",
	searchTerms: []
},
{
	title: "fa fa-google-wallet",
	searchTerms: []
},
{
	title: "fa fa-cc-visa",
	searchTerms: []
},
{
	title: "fa fa-cc-mastercard",
	searchTerms: []
},
{
	title: "fa fa-cc-discover",
	searchTerms: []
},
{
	title: "fa fa-cc-amex",
	searchTerms: []
},
{
	title: "fa fa-cc-paypal",
	searchTerms: []
},
{
	title: "fa fa-cc-stripe",
	searchTerms: []
},
{
	title: "fa fa-bell-slash",
	searchTerms: []
},
{
	title: "fa fa-bell-slash-o",
	searchTerms: []
},
{
	title: "fa fa-trash",
	searchTerms: []
},
{
	title: "fa fa-copyright",
	searchTerms: []
},
{
	title: "fa fa-at",
	searchTerms: []
},
{
	title: "fa fa-eyedropper",
	searchTerms: []
},
{
	title: "fa fa-paint-brush",
	searchTerms: []
},
{
	title: "fa fa-birthday-cake",
	searchTerms: []
},
{
	title: "fa fa-area-chart",
	searchTerms: []
},
{
	title: "fa fa-pie-chart",
	searchTerms: []
},
{
	title: "fa fa-line-chart",
	searchTerms: []
},
{
	title: "fa fa-lastfm",
	searchTerms: []
},
{
	title: "fa fa-lastfm-square",
	searchTerms: []
},
{
	title: "fa fa-toggle-off",
	searchTerms: []
},
{
	title: "fa fa-toggle-on",
	searchTerms: []
},
{
	title: "fa fa-bicycle",
	searchTerms: []
},
{
	title: "fa fa-bus",
	searchTerms: []
},
{
	title: "fa fa-ioxhost",
	searchTerms: []
},
{
	title: "fa fa-angellist",
	searchTerms: []
},
{
	title: "fa fa-cc",
	searchTerms: []
},
{
	title: "fa fa-ils",
	searchTerms: []
},
{
	title: "fa fa-meanpath",
	searchTerms: []
},
{
	title: "fa fa-buysellads",
	searchTerms: []
},
{
	title: "fa fa-connectdevelop",
	searchTerms: []
},
{
	title: "fa fa-dashcube",
	searchTerms: []
},
{
	title: "fa fa-forumbee",
	searchTerms: []
},
{
	title: "fa fa-leanpub",
	searchTerms: []
},
{
	title: "fa fa-sellsy",
	searchTerms: []
},
{
	title: "fa fa-shirtsinbulk",
	searchTerms: []
},
{
	title: "fa fa-simplybuilt",
	searchTerms: []
},
{
	title: "fa fa-skyatlas",
	searchTerms: []
},
{
	title: "fa fa-cart-plus",
	searchTerms: []
},
{
	title: "fa fa-cart-arrow-down",
	searchTerms: []
},
{
	title: "fa fa-diamond",
	searchTerms: []
},
{
	title: "fa fa-ship",
	searchTerms: []
},
{
	title: "fa fa-user-secret",
	searchTerms: []
},
{
	title: "fa fa-motorcycle",
	searchTerms: []
},
{
	title: "fa fa-street-view",
	searchTerms: []
},
{
	title: "fa fa-heartbeat",
	searchTerms: []
},
{
	title: "fa fa-venus",
	searchTerms: []
},
{
	title: "fa fa-mars",
	searchTerms: []
},
{
	title: "fa fa-mercury",
	searchTerms: []
},
{
	title: "fa fa-transgender",
	searchTerms: []
},
{
	title: "fa fa-transgender-alt",
	searchTerms: []
},
{
	title: "fa fa-venus-double",
	searchTerms: []
},
{
	title: "fa fa-mars-double",
	searchTerms: []
},
{
	title: "fa fa-venus-mars",
	searchTerms: []
},
{
	title: "fa fa-mars-stroke",
	searchTerms: []
},
{
	title: "fa fa-mars-stroke-v",
	searchTerms: []
},
{
	title: "fa fa-mars-stroke-h",
	searchTerms: []
},
{
	title: "fa fa-neuter",
	searchTerms: []
},
{
	title: "fa fa-genderless",
	searchTerms: []
},
{
	title: "fa fa-facebook-official",
	searchTerms: []
},
{
	title: "fa fa-pinterest-p",
	searchTerms: []
},
{
	title: "fa fa-whatsapp",
	searchTerms: []
},
{
	title: "fa fa-server",
	searchTerms: []
},
{
	title: "fa fa-user-plus",
	searchTerms: []
},
{
	title: "fa fa-user-times",
	searchTerms: []
},
{
	title: "fa fa-bed",
	searchTerms: []
},
{
	title: "fa fa-viacoin",
	searchTerms: []
},
{
	title: "fa fa-train",
	searchTerms: []
},
{
	title: "fa fa-subway",
	searchTerms: []
},
{
	title: "fa fa-medium",
	searchTerms: []
},
{
	title: "fa fa-y-combinator",
	searchTerms: []
},
{
	title: "fa fa-optin-monster",
	searchTerms: []
},
{
	title: "fa fa-opencart",
	searchTerms: []
},
{
	title: "fa fa-expeditedssl",
	searchTerms: []
},
{
	title: "fa fa-battery-full",
	searchTerms: []
},
{
	title: "fa fa-battery-three-quarters",
	searchTerms: []
},
{
	title: "fa fa-battery-half",
	searchTerms: []
},
{
	title: "fa fa-battery-quarter",
	searchTerms: []
},
{
	title: "fa fa-battery-empty",
	searchTerms: []
},
{
	title: "fa fa-mouse-pointer",
	searchTerms: []
},
{
	title: "fa fa-i-cursor",
	searchTerms: []
},
{
	title: "fa fa-object-group",
	searchTerms: []
},
{
	title: "fa fa-object-ungroup",
	searchTerms: []
},
{
	title: "fa fa-sticky-note",
	searchTerms: []
},
{
	title: "fa fa-sticky-note-o",
	searchTerms: []
},
{
	title: "fa fa-cc-jcb",
	searchTerms: []
},
{
	title: "fa fa-cc-diners-club",
	searchTerms: []
},
{
	title: "fa fa-clone",
	searchTerms: []
},
{
	title: "fa fa-balance-scale",
	searchTerms: []
},
{
	title: "fa fa-hourglass-o",
	searchTerms: []
},
{
	title: "fa fa-hourglass-start",
	searchTerms: []
},
{
	title: "fa fa-hourglass-half",
	searchTerms: []
},
{
	title: "fa fa-hourglass-end",
	searchTerms: []
},
{
	title: "fa fa-hourglass",
	searchTerms: []
},
{
	title: "fa fa-hand-rock-o",
	searchTerms: []
},
{
	title: "fa fa-hand-paper-o",
	searchTerms: []
},
{
	title: "fa fa-hand-scissors-o",
	searchTerms: []
},
{
	title: "fa fa-hand-lizard-o",
	searchTerms: []
},
{
	title: "fa fa-hand-spock-o",
	searchTerms: []
},
{
	title: "fa fa-hand-pointer-o",
	searchTerms: []
},
{
	title: "fa fa-hand-peace-o",
	searchTerms: []
},
{
	title: "fa fa-trademark",
	searchTerms: []
},
{
	title: "fa fa-registered",
	searchTerms: []
},
{
	title: "fa fa-creative-commons",
	searchTerms: []
},
{
	title: "fa fa-gg",
	searchTerms: []
},
{
	title: "fa fa-gg-circle",
	searchTerms: []
},
{
	title: "fa fa-tripadvisor",
	searchTerms: []
},
{
	title: "fa fa-odnoklassniki",
	searchTerms: []
},
{
	title: "fa fa-odnoklassniki-square",
	searchTerms: []
},
{
	title: "fa fa-get-pocket",
	searchTerms: []
},
{
	title: "fa fa-wikipedia-w",
	searchTerms: []
},
{
	title: "fa fa-safari",
	searchTerms: []
},
{
	title: "fa fa-chrome",
	searchTerms: []
},
{
	title: "fa fa-firefox",
	searchTerms: []
},
{
	title: "fa fa-opera",
	searchTerms: []
},
{
	title: "fa fa-internet-explorer",
	searchTerms: []
},
{
	title: "fa fa-television",
	searchTerms: []
},
{
	title: "fa fa-contao",
	searchTerms: []
},
{
	title: "fa fa-500px",
	searchTerms: []
},
{
	title: "fa fa-amazon",
	searchTerms: []
},
{
	title: "fa fa-calendar-plus-o",
	searchTerms: []
},
{
	title: "fa fa-calendar-minus-o",
	searchTerms: []
},
{
	title: "fa fa-calendar-times-o",
	searchTerms: []
},
{
	title: "fa fa-calendar-check-o",
	searchTerms: []
},
{
	title: "fa fa-industry",
	searchTerms: []
},
{
	title: "fa fa-map-pin",
	searchTerms: []
},
{
	title: "fa fa-map-signs",
	searchTerms: []
},
{
	title: "fa fa-map-o",
	searchTerms: []
},
{
	title: "fa fa-map",
	searchTerms: []
},
{
	title: "fa fa-commenting",
	searchTerms: []
},
{
	title: "fa fa-commenting-o",
	searchTerms: []
},
{
	title: "fa fa-houzz",
	searchTerms: []
},
{
	title: "fa fa-vimeo",
	searchTerms: []
},
{
	title: "fa fa-black-tie",
	searchTerms: []
},
{
	title: "fa fa-fonticons",
	searchTerms: []
} ]
    });
});