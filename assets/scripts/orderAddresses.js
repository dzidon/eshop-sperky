import { loaderOpen } from './app';
import { loaderClose } from './app';
import { errorModalOpen } from './app';

$(document).ready(function()
{
    const checkbox = $('.company-checkbox');

    checkbox.each(function()
    {
        hideOrShowCompanyFields(this);
    });

    checkbox.change(function()
    {
        hideOrShowCompanyFields(this);
    });
});

function hideOrShowCompanyFields(checkbox)
{
    const parent = $('#company-fields');
    const children = $("#company-fields :input");

    if(checkbox.checked)
    {
        parent.show();
        children.attr("disabled", false);
    }
    else
    {
        parent.hide();
        children.attr("disabled", true);
    }
}