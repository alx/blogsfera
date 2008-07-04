</div><!-- wpbody -->
</div><!-- wpcontent -->
</div><!-- wpwrap -->
<div id="footer">
<p><?php
do_action('in_admin_footer', '');
$footer_text = __('Thank you for creating with <a href="http://mu.wordpress.org/">WordPress MU</a>') . ' | ' . __('<a href="http://mu.wordpress.org/docs/">Documentation</a>');
echo apply_filters( 'admin_footer_text', $footer_text );
?></p>
</div>
<?php do_action('admin_footer', ''); ?>
<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
</body>
</html>
