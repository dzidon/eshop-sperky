import tinymce from 'tinymce';

import 'tinymce/icons/default';

import 'tinymce/themes/silver';

import 'tinymce/skins/ui/oxide/skin.css';

import 'tinymce/plugins/advlist';
import 'tinymce/plugins/code';
import 'tinymce/plugins/emoticons';
import 'tinymce/plugins/emoticons/js/emojis';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/table';

import 'tinymce/models/dom';

$(document).ready(function() {
    tinymce.init({
        selector: '.tinymce-editor',
        plugins: 'advlist code emoticons link lists table',
        menubar: 'edit insert view format table tools table',
        toolbar: 'undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | outdent indent',
        language: 'cs',
        language_url : '/build/tinymceCzech.js',
        promotion: false,
        branding: false,
    });

    // aktivuje label, aby byl videt
    setTimeout(function() { $('.tox-tinymce + label').addClass('active'); }, 10);
});