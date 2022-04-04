import 'materialize-css/dist/css/materialize.min.css';
require('materialize-css/dist/js/materialize.min');
import 'materialize-css/extras/noUiSlider/nouislider.css';
import '../styles/app.css';

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
    $('.tooltipped').tooltip();
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
    initializeAddToCartLinks();
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
    loaderOpen();

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

        if (typeof(data['totalProducts']) != "undefined" && data['totalProducts'] !== null)
        {
            $('.navbar-cart-total-products').text(data['totalProducts']);
        }
    })
    .fail(function (jqXHR)
    {
        const data = jqXHR['responseJSON'];
        if (typeof(data) != "undefined" && Array.isArray(data['errors']) && data['errors'].length > 0)
        {
            const errors = data['errors'].join('<br>');
            errorModalOpen(errors);
        }
        else
        {
            errorModalOpen('Nepodařilo se vložit produkt do košíku, zkuste to prosím znovu.');
        }
    })
    .always(function ()
    {
        loaderClose();
    });
}

export function initializeAddToCartLinks()
{
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
}

export function loaderOpen(text = 'Načítání...')
{
    $('#modal-loader-text').text(text);
    M.Modal.getInstance($('#modal-loader')).open();
}

export function loaderClose()
{
    M.Modal.getInstance($('#modal-loader')).close();
}

export function errorModalOpen(htmlContent, headerText = 'Chyba')
{
    $('#modal-error-text').html(htmlContent);
    $('#modal-error-heading').text(headerText);
    M.Modal.getInstance($('#modal-error')).open();
}