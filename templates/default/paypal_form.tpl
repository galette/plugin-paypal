<h1 id="titre">{_T string="Paypal payment"}</h1>
<form action="https://www.sandbox.paypal.com/fr/cgi-bin/webscr" method="post">
<!--<form action="https://www.paypal.com/cgi-bin/webscr" method="post">--><!-- TODO: switch beetween dev/prod modes-->
    <!-- Paypal required variables -->
    <input type="hidden" name="cmd" value="_xclick"/>
    <input type="hidden" name="business" value="{$business}"/>
    <input type="hidden" name="lc" value="US"/><!-- notfound :( -->
    <input type="hidden" name="currency_code" value="EUR"/>{* FIXME: parameter? *}
    <input type="hidden" name="button_subtype" value="services"/>
    <input type="hidden" name="no_note" value="1"/>
    <input type="hidden" name="no_shipping" value="1"/>
    <input type="hidden" name="bn" value="PP-BuyNowBF:btn_buynowCC_LG.gif:NonHostedGuest"/><!-- notfound :( -->
    <!-- Paypal dialogs -->
    <input type="hidden" name="return" value=""/><!-- TODO: -->
    <input type="hidden" name="rm" value="POST"/>{*Send POST values back to Galette after payment. Will be sent to return url above*}
    <input type="hidden" name="image_url" value=""/><!-- TODO: -->
    <input type="hidden" name="cancel_return" value=""/><!-- TODO: -->
    <input type="hidden" name="notify_url" value=""/><!-- TODO: -->

    <p>{_T string="Select an option below, then click 'Payment' to proceed. Once your paiment validated, an entry will be automatically added in the contributions table and staff members will receive a notification mail."}</p>

{if $amounts|@count gt 0}
    <ul>
    {foreach from=$amounts item=amount}
        <li>
            <input type="radio" name="item_name" id="in{$amount[1]}" value="{$amount[0]}"/>
            <label for="in{$amount[1]}">{$amount[0]}
        {if $amount[2] gt 0}
                ({$amount[2]|string_format:"%.2f"} €)
        {/if}
            </label>
        </li>
    {/foreach}
    </ul>
{else}
    <p>{_T string="No predefined amounts have been defined yet."}</p>
{/if}

    <p>{_T string="Enter an amount (with a minimum that correspond to the option you've selected below)."}</p>
    <label for="amount">{_T string="Amount"}</label>
    <input type="text" name="amount" id="amount" value="20.00"/>

    <div class="button-container">
        <input type="submit" class="submit" name="submit" value="{_T string="Payment"}"/>
    </div>
</form>
