<?php
/**
 * The none view file of product module of ZenTaoPMS.
 *
 * @copyright   Copyright 2009-2015 青岛易软天创网络科技有限公司(QingDao Nature Easy Soft Network Technology Co,LTD, www.cnezsoft.com)
 * @license     ZPL (http://zpl.pub/page/zplv12.html)
 * @author      Yidong Wang <yidong@cnezsoft.com>
 * @package     product
 * @version     $Id$
 * @link        http://www.zentao.net
 */
?>
<?php include '../../common/view/header.html.php';?>
<?php if($fromModule != 'product' && !empty($project)):?>
<div class="table-empty-tip">
  <p><span class="text-muted"><?php echo $lang->product->noProduct;?></span> <?php common::printLink('program', 'PRJManageProducts', "projectID=$project->id&programID=$project->parent", "<i class='icon icon-plus'></i> " . $lang->program->PRJManageProducts, '', "class='btn btn-info'");?></p>
</div>
<script>$("#menuNav .nav li a[href*='product']").closest('li').removeClass('active');</script>
<?php else:?>
<div class="table-empty-tip">
  <p><span class="text-muted"><?php echo $lang->product->noProduct;?></span> <?php common::printLink('product', 'create', '', "<i class='icon icon-plus'></i> " . $lang->product->create, '', "class='btn btn-info'");?></p>
</div>
<?php endif;?>
<?php include '../../common/view/footer.html.php';?>

