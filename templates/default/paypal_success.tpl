{extends file="page.tpl"}
{block name="content"}
{if $post}
<p>{_T string="Your paypal payment was successful. Some details are shown below:" domain="paypal"}</p>
<table>
    <tr>
        <th>{_T string="Label"}</th>
        <td>{$post.item_name}</td>
    </tr>
    <tr>
        <th>{_T string="Payment date"}</th>
        <td>{$post.payment_date}</td>
    </tr>
    <tr>
        <th>{_T string="Payment status"}</th>
        <td>{$post.payment_status}</td>
    </tr>
    <tr>
        <th>{_T string="Payment type"}</th>
        <td>{$post.payment_type}</td>
    </tr>
    <tr>
        <th>{_T string="Amount"}</th>
        <td>{$post.mc_gross} {$post.mc_currency}</td>
    </tr>
</table>
{else}
<p>{_T string="Your paypal payment was successful. You may receive a mail from paypal with details." domain="paypal"}</p>
{/if}
{/block}
