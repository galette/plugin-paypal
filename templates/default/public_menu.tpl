        {if $public_page}
            <a id="ppaypal" class="button{if $cur_route eq "paypal_form" or $cur_route eq "paypal_success"} selected{/if}" href="{path_for name="paypal_form"}">{_T string="Payment form" domain="paypal"}</a>
        {/if}
