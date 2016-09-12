<div class="login">
 <h1>Shinken</h1>
 <h2>{$product}</h2>
 {if $message}
 <div class="card-panel {$message.style}">
  {$message.text}
 </div>
 {/if}
 <div class="card-panel">
  <h3>Sign In</h3>
  <form method="post" action="">
   <p><input type="text" name="username" placeholder="Username" /></p>
   <p><input type="password" name="password" placeholder="Password" /></p>
   <p><input type="submit" value="Sign In" class="btn deep-orange darken-4" /></p>
  </form>
 </div>
</div>
