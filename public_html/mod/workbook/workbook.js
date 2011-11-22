/**
 * Javascript to insert the field tags into the textarea.
 * Used when editing a workbook template
 */
function insert_field_tags(selectlist) {
    var value = selectlist.options[selectlist.selectedIndex].value;
    var editorname = 'template';
    if (typeof tinyMCE == 'undefined') {
        var element = document.getElementsByName(editorname)[0];
        // For inserting when in normal textareas
        insertAtCursor(element, value);
    } else {
        tinyMCE.execInstanceCommand(editorname, 'mceInsertContent', false, value);
    }
}

/**
 * javascript for hiding/displaying advanced search form when viewing
 */
function showHideAdvSearch(checked) {
    var divs = document.getElementsByTagName('div');
    for(i=0;i<divs.length;i++) {
        if(divs[i].id.match('workbook_adv_form')) {
            if(checked) {
                divs[i].style.display = 'inline';
            }
            else {
                divs[i].style.display = 'none';
            }
        }
        else if (divs[i].id.match('reg_search')) {
            if (!checked) {
                divs[i].style.display = 'inline';
            }
            else {
                divs[i].style.display = 'none';
            }
        }
    }
}

M.workbook_filepicker = {};


M.workbook_filepicker.callback = function(params) {
    var html = '<a href="'+params['url']+'">'+params['file']+'</a>';
    document.getElementById('file_info_'+params['client_id']).innerHTML = html;
};

/**
 * This fucntion is called for each file picker on page.
 */
M.workbook_filepicker.init = function(Y, options) {
    options.formcallback = M.workbook_filepicker.callback;
    if (!M.core_filepicker.instances[options.client_id]) {
        M.core_filepicker.init(Y, options);
    }
    Y.on('click', function(e, client_id) {
        e.preventDefault();
        M.core_filepicker.instances[client_id].show();
    }, '#filepicker-button-'+options.client_id, null, options.client_id);

    var item = document.getElementById('nonjs-filepicker-'+options.client_id);
    if (item) {
        item.parentNode.removeChild(item);
    }
    item = document.getElementById('filepicker-wrapper-'+options.client_id);
    if (item) {
        item.style.display = '';
    }
};

M.workbook_urlpicker = {};

M.workbook_urlpicker.init = function(Y, options) {
    options.formcallback = M.workbook_urlpicker.callback;
    if (!M.core_filepicker.instances[options.client_id]) {
        M.core_filepicker.init(Y, options);
    }
    Y.on('click', function(e, client_id) {
        e.preventDefault();
        M.core_filepicker.instances[client_id].show();
    }, '#filepicker-button-'+options.client_id, null, options.client_id);

};

M.workbook_urlpicker.callback = function (params) {
    document.getElementById('field_url_'+params.client_id).value = params.url;
};

M.workbook_imagepicker = {};

M.workbook_imagepicker.callback = function(params) {
    var html = '<a href="'+params['url']+'"><img src="'+params['url']+'" /> '+params['file']+'</a>';
    document.getElementById('file_info_'+params['client_id']).innerHTML = html;
};

/**
 * This fucntion is called for each file picker on page.
 */
M.workbook_imagepicker.init = function(Y, options) {
    options.formcallback = M.workbook_imagepicker.callback;
    if (!M.core_filepicker.instances[options.client_id]) {
        M.core_filepicker.init(Y, options);
    }
    Y.on('click', function(e, client_id) {
        e.preventDefault();
        M.core_filepicker.instances[client_id].show();
    }, '#filepicker-button-'+options.client_id, null, options.client_id);

    var item = document.getElementById('nonjs-filepicker-'+options.client_id);
    if (item) {
        item.parentNode.removeChild(item);
    }
    item = document.getElementById('filepicker-wrapper-'+options.client_id);
    if (item) {
        item.style.display = '';
    }
};
