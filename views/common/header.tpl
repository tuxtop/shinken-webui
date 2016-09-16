<header class="shinken">
 <div class="bg">
  <div class="container">
   <div class="row">
    <div class="col s6">
     <h1>Shinken</h1>
     <h2>{$product}</h2>
    </div>
    <div class="col s5 right-align">
     <form method="get" action="/search">
      <input type="text" name="filter" placeholder="Host, hostgroup, service" value="{$search}" />
     </form>
    </div>
    <div class="col s1 right-align">
     <a href="#!" class="dropdown-button bookmark" data-alignment="right" data-activates="bookmarks"><i class="material-icons">star</i></a>
     <ul id="bookmarks" class="dropdown-content">
      {loop $bookmarks}
       <li><a href="{$url}">{$name}</a></li>
      {/loop}
     </ul>
     <a href="#!" class="button-collapse menu" data-activates="menu"><i class="material-icons">more_vert</i></a>
    </div>
   </div>
  </div>
 </div>
</header>

<ul id="menu" class="side-nav">
 <li class="no-padding">
  <ul class="collapsible collapsible-accordion">
   <li>
    <a href="#" class="collapsible-header">H&amp;S Status</a>
    <div class="collapsible-body">
     <ul>
      <li><a href="/search?filter=type:hosts"><i class="material-icons">storage</i>Hosts</a></li>
      <li><a href="/search?filter=type:services"><i class="material-icons">network_check</i>Services</a></li>
     </ul>
    </div>
   </li>
  </ul>
  <ul class="collapsible collapsible-accordion">
   <li>
    <a href="#" class="collapsible-header">Configuration</a>
    <div class="collapsible-body">
     <ul>
      <li><a href="/hostgroups"><i class="material-icons">group_work</i>Hosts groups</a></li>
      <li><a href="/contactgroups"><i class="material-icons">group</i>Contacts groups</a></li>
      <li><a href="/contacts"><i class="material-icons">contacts</i>Contacts</a></li>
     </ul>
    </div>
   </li>
  </ul>
 </li>
 <li><a href="/shinken"><i class="material-icons">favorite</i>Shinken Status</a></li>
</ul>

<script type="text/javascript"> 
$(document).ready(function(){
	$(".button-collapse").sideNav();
	$(".collapsible").collapsible();
});
</script>
