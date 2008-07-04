<?php 
header('Content-type: text/css');
?>

/* Put this inside a @media qualifier so Netscape 4 ignores it */
@media screen, print { 
	/* Turn off list bullets */
	ul.mktree  li { list-style: none; } 
	/* Control how "spaced out" the tree is */
	ul.mktree, ul.mktree ul , ul.mktree li { margin-left:10px; padding:0px; }
	/* Provide space for our own "bullet" inside the LI */
	ul.mktree  li           .bullet { padding-left: 15px; }
	/* Show "bullets" in the links, depending on the class of the LI that the link's in */
	ul.mktree  li.liOpen    .bullet { cursor: pointer; background: url('../img/minus.gif')  center left no-repeat; }
	ul.mktree  li.liClosed  .bullet { cursor: pointer; background: url('../img/plus.gif')  center left no-repeat; }
	ul.mktree  li.liBullet  .bullet { cursor: default; background: url('../img/bullet.gif') center left no-repeat; }
	/* Sublists are visible or not based on class of parent LI */
	ul.mktree  li.liOpen    ul { display: block; }
	ul.mktree  li.liClosed  ul { display: none; }
	/* Format menu items differently depending on what level of the tree they are in */
	ul.mktree  li { font-size: 12pt; }
	ul.mktree  li ul li { font-size: 10pt; }
	ul.mktree  li ul li ul li { font-size: 8pt; }
	ul.mktree  li ul li ul li ul li { font-size: 6pt; }
}
