import 'materialize-css/dist/css/materialize.min.css';
require('materialize-css/dist/js/materialize.min');
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

    // materialize autocomplete
    $('input.autocomplete').each(function() {
        $(this).autocomplete({
            data: JSON.parse(
                $(this).attr('data-autocomplete')
            ),
            limit: autocompleteMaxElements
        });
    });

    // smooth scroll na celý popis po kliknutí na odkaz "(Celý popis)"
    $("#full-description-link").click(function(e) {
        e.preventDefault();
        const target = document.getElementById('full-description');
        target.scrollIntoView({
            behavior: 'auto',
            block: 'center',
            inline: 'center'
        });
    });

    // obrázky na stránce produktu
    $('.black-and-white-img:first').removeClass('black-and-white-img');

    const productImageLarge = $('#product-image-large');
    $('.product-image-small-link').click(function(e) {
        e.preventDefault()

        const clickedImage = $(this).children("img:first");
        const src = clickedImage.attr('src');
        productImageLarge.attr('src', src);

        $('.product-image-small-link').find('img').addClass('black-and-white-img');
        clickedImage.removeClass('black-and-white-img');
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