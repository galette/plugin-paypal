        {if !$public_page}
            <li{if $PAGENAME eq "paypal_form.php" or $PAGENAME eq "paypal_success.php"} class="selected"{/if}><a href="{$galette_base_path}{$galette_galette_paypal_path}paypal_form.php">{_T string="Payment form"}</a></li>
        {else}
            <a id="ppaypal" class="button{if $PAGENAME eq "paypal_form.php" or $PAGENAME eq "paypal_success.php"} selected{/if}" href="{$galette_base_path}{$galette_galette_paypal_path}paypal_form.php">{_T string="Payment form"}</a>
        {/if}
