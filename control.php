<?
// Задача 3
// В самой задаче сказано "Верстка", значит делаем только верстку. Никакого функционала не будет.
?>
<style>
    /* Блок контрола */
    .control {
        padding: 20px;
    }

    .control__button {
        padding: 1px;
        border-radius: 6px;
        display: inline-block;

        background: linear-gradient(0deg, rgba(0, 0, 0, .35), rgba(0, 0, 0, .10));
    }

    .control__button > button {
        /* Typgraphy */
        font-size: 11px;
        font-family: Verdana, sans-serif;
        color: #000;
        text-shadow: 0 1px 0 rgba(255, 255, 255, .75);
        line-height: 1.00;

        border: none;

        /* Layout */
        position: relative;
        padding: 5px 20px;
        border-radius: 5px;
    }

    .control__button > button,
    .control__button > button:focus,
    .control__button > button:focus:hover,
    .control__button > button:active,
    .control__button > button:active:hover {
        background: #fff linear-gradient(0deg, #e2e2e2 30%, #f2f2f2 70%);
    }

    .control__button > button:hover {
        background: #fff linear-gradient(0deg, #fdffcc 30%, #feffeb 70%);
    }

    .control__button > button:focus {
        box-shadow: 0 0 4px rgba(0, 35, 214, .94);
    }

    .control__button > button:active {
        padding: 6px 20px 4px;
        box-shadow: inset 0 2px 3px rgba(0, 0, 0, .26);
    }


    /* Блок рейтинга */
    .rating {
        width: 80px;
        display: flex;
        flex-direction: row-reverse;
        margin-bottom: 80px;
    }

    .rating > input {
        display: none;
    }

    .rating label {
        content: '';
        width: 18px;
        height: 16px;
        background: url(img/sprite.png) no-repeat 0 0;
        cursor: pointer;
    }

    .rating label:hover,
    .rating label:hover ~ .rating label {
        background-position-y: -16px;
    }
</style>
<div class="control">
    <div class="rating">
        <input class="rating__star" type="radio" name="rating" value="5" id="5"/><label for="5"></label>
        <input class="rating__star" type="radio" name="rating" value="4" id="4"/><label for="4"></label>
        <input class="rating__star" type="radio" name="rating" value="3" id="3"/><label for="3"></label>
        <input class="rating__star" type="radio" name="rating" value="2" id="2"/><label for="2"></label>
        <input class="rating__star" type="radio" name="rating" value="1" id="1"/><label for="1"></label>
    </div>
    <div class="control__button">
        <button>Кнопка</button>
    </div>
</div>