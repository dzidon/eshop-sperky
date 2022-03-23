import 'materialize-css/dist/css/materialize.min.css';
require('materialize-css/dist/js/materialize.min');
import 'materialize-css/extras/noUiSlider/nouislider.css';
import './styles/app.css';

const autocompleteMaxElements = 5;
$(document).ready(function() {

    // materialize
    $('.sidenav').sidenav();
    $('.parallax').parallax();
    $('.collapsible').collapsible();
    $('.tabs').tabs();
    $('.materialboxed').materialbox();
    $('select').formSelect();
    $('.dropdown-trigger').dropdown({ hover: false });
    $('.modal').modal();
    $('textarea[data-length]').characterCounter();
    $('#modal-loader').modal({
        dismissible: false
    });

    // materialize autocomplete
    $('input.autocomplete').each(function()
    {
        $(this).autocomplete({
            data: JSON.parse(
                $(this).attr('data-autocomplete')
            ),
            limit: autocompleteMaxElements
        });
    });

    // dynamicke vytvareni inputu podle prototypu pro CollectionType
    document
        .querySelectorAll('.js-add-item-link')
        .forEach(btn => {
            btn.addEventListener("click", addFormToCollection)
    });

    // scroll prvniho erroru na obrazovku
    const firstError = document.getElementsByClassName('form-error')[0];
    if(firstError)
    {
        firstError.scrollIntoView({
            behavior: 'auto',
            block: 'center',
            inline: 'center'
        });
    }

    // vkládání produktů do košíku přes odkaz v náhledu
    const productImageLarge = $('.cart-insert-link');
    productImageLarge.click(function()
    {
        const wrapper = $(this).closest('.insertable-products-wrapper');
        const csrfToken = wrapper.data('cart-insert-csrf-token');
        const url = wrapper.data('cart-insert-url');
        const data = {
            'cart_insert_form': {
                'productId': $(this).data('product-id'),
                'quantity': 1,
                '_token': csrfToken,
            }
        };

        ajaxAddProductToCart(url, data);
    });
});

/*
 * Přidá formulář do CollectionType
 */
const addFormToCollection = (e) =>
{
    const collectionHolder = document.querySelector('.' + e.currentTarget.dataset.collectionHolderClass);
    const item = document.createElement('li');

    item.innerHTML = collectionHolder
        .dataset
        .prototype
        .replace(
            /__name__/g,
            collectionHolder.dataset.index
        )
    ;

    collectionHolder.appendChild(item.firstElementChild);
    collectionHolder.dataset.index++;

    // Možná chceme reloadnout dropdown (kvůli tomu jak funguje Materialize)
    const reloadSelect = collectionHolder.dataset.reloadSelect;
    if(reloadSelect)
    {
        const element = collectionHolder.lastElementChild.querySelectorAll('select');
        M.FormSelect.init(element);
    }

    // Možná chceme reloadnout dropdown (kvůli tomu jak funguje Materialize)
    const reloadAutocomplete = collectionHolder.dataset.reloadAutocomplete;
    if(reloadAutocomplete)
    {
        const element = collectionHolder.lastElementChild.querySelectorAll('input.autocomplete');

        $(element).each(function() {
            $(this).autocomplete({
                data: JSON.parse(
                    $(this).attr('data-autocomplete')
                ),
                limit: autocompleteMaxElements
            });
        });
    }
};

export function ajaxAddProductToCart(url, data)
{
    M.Modal.getInstance($('#modal-loader')).open();

    $.post({
        url: url,
        data: data,
        dataType: 'json',
    })
    .done(function (data)
    {
        if (jQuery.type(data['html']) === "string")
        {
            $('#modal-content-cart-insert-inner').html(data['html']);
            M.Modal.getInstance($('#modal-cart-insert')).open();
        }
    })
    .fail(function (jqXHR)
    {
        const data = jqXHR['responseJSON'];
        if (Array.isArray(data['errors']) && data['errors'].length > 0)
        {
            const errors = data['errors'].join('<br>');
            $('#modal-error-text').html(errors);
        }
        else
        {
            $('#modal-error-text').text('Nepodařilo se vložit produkt do košíku, zkuste to prosím znovu.')
        }
        M.Modal.getInstance($('#modal-error')).open();
    })
    .always(function ()
    {
        M.Modal.getInstance($('#modal-loader')).close();
    });
}