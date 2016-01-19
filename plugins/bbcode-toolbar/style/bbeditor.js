/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

/*****************************************/
// Inspired by :
// Name: Javascript Textarea BBCode Markup Editor
// Version: 1.3
// Author: Balakrishnan
// Last Modified Date: 25/jan/2009
// License: Free
// URL: http://www.corpocrat.com
/******************************************/

var textarea,
    content,
    target = document.getElementById( 'req_message' ),
     toolbar = postEditorToolbar('req_message');

target.insertAdjacentHTML( 'beforeBegin', toolbar );

function postEditorToolbar(obj) {
    // Get translations from js block in footer
    var langBbeditor = JSON.parse(phpVars.bbcodeToolbar),
        output = '';
    // Toolbar buttons
    output += "<div class=\"toolbar\">";
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/bold.png\" name=\"btnBold\" title=\""+langBbeditor.btnBold+"\" onClick=\"doAddTags('[b]','[/b]','" + obj + "')\">";
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/italic.png\" name=\"btnItalic\" title=\""+langBbeditor.btnItalic+"\" onClick=\"doAddTags('[i]','[/i]','" + obj + "')\">";
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/underline.png\" name=\"btnUnderline\" title=\""+langBbeditor.btnUnderline+"\" onClick=\"doAddTags('[u]','[/u]','" + obj + "')\">";
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/eyedropper.png\" name=\"btnColor\" title=\""+langBbeditor.btnColor+"\" onClick=\"toggleColorpicker()\">";
        output += '<span class="toolbar-separator"></span>';
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/align-left.png\" name=\"btnLeft\" title=\""+langBbeditor.btnLeft+"\" onClick=\"doAddTags('[left]','[/left]','" + obj + "')\">";
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/align-right.png\" name=\"btnRight\" title=\""+langBbeditor.btnRight+"\" onClick=\"doAddTags('[right]','[/right]','" + obj + "')\">";
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/align-justify.png\" name=\"btnJustify\" title=\""+langBbeditor.btnJustify+"\" onClick=\"doAddTags('[justify]','[/justify]','" + obj + "')\">";
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/align-center.png\" name=\"btnCenter\" title=\""+langBbeditor.btnCenter+"\" onClick=\"doAddTags('[center]','[/center]','" + obj + "')\">";
        output += '<span class="toolbar-separator"></span>';
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/link.png\" name=\"btnLink\" title=\""+langBbeditor.btnLink+"\" onClick=\"doURL('" + obj + "')\">";
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/file-image-o.png\" name=\"btnPicture\" title=\""+langBbeditor.btnPicture+"\" onClick=\"doImage('" + obj + "')\">";
        output += '<span class="toolbar-separator"></span>';
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/list-ol.png\" name=\"btnList\" title=\""+langBbeditor.btnList+"\" onClick=\"doList('[list=1]','[/list]','" + obj + "')\">";
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/list-ul.png\" name=\"btnList\" title=\""+langBbeditor.btnList+"\" onClick=\"doList('[list]','[/list]','" + obj + "')\">";
        output += '<span class="toolbar-separator"></span>';
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/quote-left.png\" name=\"btnQuote\" title=\""+langBbeditor.btnQuote+"\" onClick=\"doQuote('" + obj + "')\">";
        output += "<img class=\"toolbar-icon\" src=\""+baseUrl+"/style/img/bbeditor/code.png\" name=\"btnCode\" title=\""+langBbeditor.btnCode+"\" onClick=\"doAddTags('[code]','[/code]','" + obj + "')\">";
        // output += "<i class=\"fa fa-smile-o toolbar-icon\" title=\"Smilies\" onClick=\"doSmiley('" + obj + "')\"></i>");
    output += "</div>";

    // Toolbar color picker
    output += '<span class="colorpicker" id="colorpicker">\
        <span class="bgbox"></span>\
        <span class="hexbox"></span>\
        <span class="clear" style="border-top:1px solid #999;border-bottom:1px solid #fff;"></span>\
        <span class="colorbox" id="colorbox">\
            <b class="selected" style="background:#007fff" title="Azure"></b>\
            <b style="background:#626878" title="Charcoal"></b>\
            <b style="background:#2E436E" title="Navy Blue"></b>\
            <b style="background:#8db600" title="Apple Green"></b>\
            <b style="background:#ffef00" title="Canary Yellow"></b>\
            <b style="background:#ed872d" title="Cadmium Orange"></b>\
            <b style="background:#e62020" title="Lust"></b>\
        </span>\
    </span>';

    return output;
}

// Close color picker content on color selected
var colorCells = document.getElementById('colorbox').getElementsByTagName("b");
for(var i=0; i<colorCells.length; i++) {
    colorCells[i].onclick=function(event) { toggleColorpicker() }
}


function doImage(obj) {
    textarea = document.getElementById(obj);
    var url = prompt(langBbeditor.promptImage, 'http://'),
        scrollTop = textarea.scrollTop,
        scrollLeft = textarea.scrollLeft;

    if (url != '' && url != null) {

        if (document.selection) {
            textarea.focus();
            var sel = document.selection.createRange();
            sel.text = '[img]' + url + '[/img]';
        } else {
            var len = textarea.value.length,
                start = textarea.selectionStart,
                end = textarea.selectionEnd,

                sel = textarea.value.substring(start, end),
                //alert(sel);
                rep = '[img]' + url + '[/img]';
            textarea.value = textarea.value.substring(0, start) + rep + textarea.value.substring(end, len);

            textarea.scrollTop = scrollTop;
            textarea.scrollLeft = scrollLeft;
        }
    }

}

function doURL(obj) {
    textarea = document.getElementById(obj);
    var url = prompt(langBbeditor.promptUrl, 'http://'),
        scrollTop = textarea.scrollTop,
        scrollLeft = textarea.scrollLeft;

    if (url != '' && url != null) {

        if (document.selection) {
            textarea.focus();
            var sel = document.selection.createRange();

            if (sel.text == "") {
                sel.text = '[url]' + url + '[/url]';
            } else {
                sel.text = '[url=' + url + ']' + sel.text + '[/url]';
            }

            //alert(sel.text);

        } else {
            var len = textarea.value.length,
                start = textarea.selectionStart,
                end = textarea.selectionEnd,

                sel = textarea.value.substring(start, end);

            if (sel == "") {
                var rep = '[url]' + url + '[/url]';
            } else {
                var rep = '[url=' + url + ']' + sel + '[/url]';
            }
            //alert(sel);

            textarea.value = textarea.value.substring(0, start) + rep + textarea.value.substring(end, len);


            textarea.scrollTop = scrollTop;
            textarea.scrollLeft = scrollLeft;
        }
    }
}

function doQuote(obj) {
    var author = prompt(langBbeditor.promptQuote),
        openTag = (author != '' && author != null) ? '[quote='+author+']' : '[quote]';

    doAddTags(openTag,'[/quote]',obj)
}

function doAddTags(tag1, tag2, obj) {
    textarea = document.getElementById(obj);
    // Code for IE
    if (document.selection) {
        textarea.focus();
        var sel = document.selection.createRange();
        sel.text = tag1 + sel.text + tag2;
    } else { // Code for Mozilla Firefox
        var len = textarea.value.length;
            start = textarea.selectionStart,
            end = textarea.selectionEnd,

            scrollTop = textarea.scrollTop,
            scrollLeft = textarea.scrollLeft,

            sel = textarea.value.substring(start, end),
            rep = tag1 + sel + tag2;

        // Update textarea content with tags
        textarea.value = textarea.value.substring(0, start) + rep + textarea.value.substring(end, len);

        // Place cursor into tags if no word selected, or after word if selection.
        if (end - start == 0) {
            var cursorPos = textarea.value.substring(0, start).length + tag1.length + sel.length;
            textarea.setSelectionRange(cursorPos, cursorPos);
        } else {
            textarea.scrollTop = scrollTop;
            textarea.scrollLeft = scrollLeft;
        }

    }
}

function doList(tag1, tag2, obj) {
    textarea = document.getElementById(obj);
    // Code for IE
    if (document.selection) {
        textarea.focus();
        var sel = document.selection.createRange();
        var list = sel.text.split('\n');

        for (i = 0; i < list.length; i++) {
            list[i] = '[*]' + list[i] + '[/*]';
        }
        //alert(list.join("\n"));
        sel.text = tag1 + '\n' + list.join("\n") + '\n' + tag2;
    } else
    // Code for Firefox
    {

        var len = textarea.value.length;
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;
        var i;

        var scrollTop = textarea.scrollTop;
        var scrollLeft = textarea.scrollLeft;


        var sel = textarea.value.substring(start, end);
        //alert(sel);

        var list = sel.split('\n');

        for (i = 0; i < list.length; i++) {
            list[i] = '[*]' + list[i] + '[/*]';
        }
        //alert(list.join("<br>"));


        var rep = tag1 + '\n' + list.join("\n") + '\n' + tag2;
        textarea.value = textarea.value.substring(0, start) + rep + textarea.value.substring(end, len);

        textarea.scrollTop = scrollTop;
        textarea.scrollLeft = scrollLeft;
    }
}

// Custom adds :

// Show or hide color picker content
function toggleColorpicker() {
    var colorpicker = document.getElementById('colorpicker'),
        display = (colorpicker.offsetParent === null) ? 'inline-block' : 'none';
    colorpicker.style.display=display;
}
function OnCustomColorChanged(selectedColor, selectedColorTitle, colorPickerIndex) {
    // alert(MC.rgbToHex(selectedColor))
    textarea = document.getElementById('req_message');
    var scrollTop = textarea.scrollTop;
    var scrollLeft = textarea.scrollLeft;

    if (document.selection) {
        textarea.focus();
        var sel = document.selection.createRange();
        sel.text = '[color=' + MC.rgbToHex(selectedColor) + ']' + sel.text + '[/color]';

        //alert(sel.text);

    } else {
        var len = textarea.value.length;
        var start = textarea.selectionStart;
        var end = textarea.selectionEnd;

        var sel = textarea.value.substring(start, end);
        var rep = '[color=' + MC.rgbToHex(selectedColor) + ']' + sel + '[/color]';
        //alert(sel);

        textarea.value = textarea.value.substring(0, start) + rep + textarea.value.substring(end, len);


        textarea.scrollTop = scrollTop;
        textarea.scrollLeft = scrollLeft;
    }
};
