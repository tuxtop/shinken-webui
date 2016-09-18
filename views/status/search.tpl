<div class="full-width-container">
 <h1 class="heading">Search</h1>
 {if $no_results}
  <div class="card-panel grey lighten-3 center-align">No match found</div>
 {/if}
 <table class="condensed striped highlight search">
  <tbody>
   {loop $results}
   <tr class="{$status.background} {$background}">
    <td class="checkbox"><input type="checkbox" name="service[{$id}]" class="default-browser" /></td>
    <td>{$hostname}</td>
    <td><a href="/status/{$url}">{$service}</a></td>
    <td class="{$status.color}-text tooltiped center-align ico" data-tooltip="{$status.tooltip}" data-position="bottom">{$status.text}</td>
    <td>{$output}</td>
   </tr>
   <tr class="opts">
    {if $longoutput}
    <td colspan="4">
    {else}
    <td colspan="5">
    {/if}
     <a href="#!" class="btn deep-orange lighten-1"><i class="material-icons">replay</i>Re-check</a>
    </td>
    {if $longoutput}
    <td>
     <div class="card-panel">{$longoutput}</div>
    </td>
    {/if}
   </tr>
   {/loop}
  </tbody>
 </table>
 <script type="text/javascript">
 $('.opts').prev('tr').on('click',function(e){
	if (e.target.type == 'checkbox') return e;
 	var a = $(this).next('.opts');
 	var b = a.css('display');
 	a.css('display', b == 'table-row' ? 'none' : 'table-row');
 });
 $('[data-tooltip]').tooltip();
 </script>
</div>
