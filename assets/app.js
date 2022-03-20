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
    $('#modal-loader').modal({
        dismissible: false
    });

    M.Modal.getInstance($('#modal-cart-insert')).open();


    // materialize autocomplete
    $('input.autocomplete').each(function() {
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