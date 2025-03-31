<?php
/**
 * 优惠券管理系统
 * 
 * 作者：阿杰
 * 电报群：https://t.me/+yK7diUyqmxI2MjZl
 * 作者邮箱：weijianao@gmail.com
 * 作者油管：https://www.youtube.com/@ajieshuo
 * 开发日期：2025年2月6日
 * 首板开发完成日期：2025年3月31日
 * 
 * 该文件主要用途是：
 * 管理商城系统的优惠券，包括添加、编辑、删除优惠券信息，
 * 以及管理优惠券的使用状态、有效期等信息。
 * 
 * 未经允许禁止商用，仅供学习研究个人使用
 */

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}
require_once '../db.php';

// 处理删除请求
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ? AND status != 'used'");
    $stmt->execute([$id]);
    header("Location: coupons.php");
    exit;
}

// 处理新增或编辑表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'generate') {
        // 批量生成优惠码
        $count = isset($_POST['count']) ? intval($_POST['count']) : 1;
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $prefix = isset($_POST['prefix']) ? trim($_POST['prefix']) : '';
        
        if ($count > 0 && $amount > 0) {
            $pdo->beginTransaction();
            try {
                for ($i = 0; $i < $count; $i++) {
                    // 生成随机优惠码
                    $code = $prefix . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
                    
                    $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_amount, status, created_at) VALUES (?, ?, 'active', NOW())");
                    $stmt->execute([$code, $amount]);
                }
                $pdo->commit();
                $success_message = "成功生成 {$count} 个优惠码";
            } catch (Exception $e) {
                $pdo->rollback();
                $error_message = "生成优惠码失败：" . $e->getMessage();
            }
        } else {
            $error_message = "请输入有效的数量和金额";
        }
    } else {
        // 单个添加优惠码
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $amount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;
        
        if (!empty($code) && $amount > 0) {
            try {
                if (isset($_POST['id']) && !empty($_POST['id'])) {
                    // 编辑优惠码
                    $id = intval($_POST['id']);
                    $stmt = $pdo->prepare("UPDATE coupons SET code = ?, discount_amount = ? WHERE id = ? AND status != 'used'");
                    $stmt->execute([$code, $amount, $id]);
                } else {
                    // 新增优惠码
                    $stmt = $pdo->prepare("INSERT INTO coupons (code, discount_amount, status, created_at) VALUES (?, ?, 'active', NOW())");
                    $stmt->execute([$code, $amount]);
                }
                $success_message = "优惠码保存成功";
            } catch (Exception $e) {
                $error_message = "保存优惠码失败：" . $e->getMessage();
            }
        } else {
            $error_message = "请输入有效的优惠码和金额";
        }
    }
}

// 查询所有优惠码
$stmt = $pdo->query("SELECT * FROM coupons ORDER BY created_at DESC");
$coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 如果有传入 id，则查询对应优惠码用于编辑
$editing = false;
if (isset($_GET['id'])) {
    $editId = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$editId]);
    $editCoupon = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($editCoupon) {
        $editing = true;
    }
}

// 获取系统配置 - 优惠码功能是否启用
try {
    $stmt = $pdo->query("SELECT * FROM system_config WHERE `key` = 'coupon_enabled' LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $coupon_enabled = isset($config['value']) ? (bool)$config['value'] : false;
} catch (Exception $e) {
    // 如果表不存在或其他错误，默认禁用优惠码功能
    $coupon_enabled = false;
}

// 处理优惠码功能开关
if (isset($_POST['toggle_coupon'])) {
    $new_status = $coupon_enabled ? 0 : 1;
    try {
        $stmt = $pdo->prepare("UPDATE system_config SET value = ? WHERE `key` = 'coupon_enabled'");
        $stmt->execute([$new_status]);
        $coupon_enabled = (bool)$new_status;
        $success_message = "优惠码功能已" . ($coupon_enabled ? "启用" : "禁用");
    } catch (Exception $e) {
        $error_message = "更新配置失败：" . $e->getMessage();
    }
}
?>
<?php
$page_title = '优惠码管理 - 管理后台';
$current_page = 'coupons';
require_once 'includes/header.php';
?>
    
    <!-- 主内容区 -->
    <main role="main" class="content">
      <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">优惠码管理</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
          <form method="post" class="mr-2">
            <input type="hidden" name="toggle_coupon" value="1">
            <button type="submit" class="btn <?php echo $coupon_enabled ? 'btn-danger' : 'btn-success'; ?>">
              <?php echo $coupon_enabled ? '禁用优惠码功能' : '启用优惠码功能'; ?>
            </button>
          </form>
          <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#generateModal">
            批量生成优惠码
          </button>
        </div>
      </div>
      
      <?php if (isset($success_message)): ?>
      <div class="alert alert-success"><?php echo $success_message; ?></div>
      <?php endif; ?>
      
      <?php if (isset($error_message)): ?>
      <div class="alert alert-danger"><?php echo $error_message; ?></div>
      <?php endif; ?>
      
      <!-- 优惠码状态 -->
      <div class="alert <?php echo $coupon_enabled ? 'alert-success' : 'alert-warning'; ?>">
        优惠码功能当前状态: <strong><?php echo $coupon_enabled ? '已启用' : '已禁用'; ?></strong>
      </div>
      
      <!-- 添加/编辑优惠码表单 -->
      <div class="card mb-4">
        <div class="card-header bg-primary text-white">
          <?php echo $editing ? '编辑优惠码' : '添加优惠码'; ?>
        </div>
        <div class="card-body">
          <form method="post">
            <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo $editCoupon['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group row">
              <label for="code" class="col-sm-2 col-form-label">优惠码</label>
              <div class="col-sm-10">
                <input type="text" class="form-control" id="code" name="code" value="<?php echo $editing ? htmlspecialchars($editCoupon['code']) : ''; ?>" required>
              </div>
            </div>
            
            <div class="form-group row">
              <label for="discount_amount" class="col-sm-2 col-form-label">优惠金额</label>
              <div class="col-sm-10">
                <div class="input-group">
                  <div class="input-group-prepend">
                    <span class="input-group-text">¥</span>
                  </div>
                  <input type="number" class="form-control" id="discount_amount" name="discount_amount" step="0.01" min="0.01" value="<?php echo $editing ? htmlspecialchars($editCoupon['discount_amount']) : ''; ?>" required>
                </div>
              </div>
            </div>
            
            <div class="form-group row">
              <div class="col-sm-10 offset-sm-2">
                <button type="submit" class="btn btn-primary"><?php echo $editing ? '更新优惠码' : '添加优惠码'; ?></button>
                <?php if ($editing): ?>
                <a href="coupons.php" class="btn btn-secondary">取消</a>
                <?php endif; ?>
              </div>
            </div>
          </form>
        </div>
      </div>
      
      <!-- 优惠码列表 -->
      <div class="card">
        <div class="card-header bg-secondary text-white">
          优惠码列表
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-hover">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>优惠码</th>
                  <th>优惠金额</th>
                  <th>状态</th>
                  <th>创建时间</th>
                  <th>使用时间</th>
                  <th>使用订单</th>
                  <th>操作</th>
                </tr>
              </thead>
              <tbody>
                <?php if (count($coupons) > 0): ?>
                  <?php foreach ($coupons as $coupon): ?>
                  <tr>
                    <td><?php echo $coupon['id']; ?></td>
                    <td><?php echo htmlspecialchars($coupon['code']); ?></td>
                    <td>¥<?php echo number_format($coupon['discount_amount'], 2); ?></td>
                    <td>
                      <?php if ($coupon['status'] == 'active'): ?>
                        <span class="badge badge-success">可用</span>
                      <?php elseif ($coupon['status'] == 'used'): ?>
                        <span class="badge badge-danger">已使用</span>
                      <?php else: ?>
                        <span class="badge badge-warning">已过期</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo $coupon['created_at']; ?></td>
                    <td><?php echo $coupon['used_at'] ? $coupon['used_at'] : '-'; ?></td>
                    <td><?php echo $coupon['used_order_no'] ? $coupon['used_order_no'] : '-'; ?></td>
                    <td>
                      <?php if ($coupon['status'] == 'active'): ?>
                        <a href="coupons.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-info">编辑</a>
                        <a href="coupons.php?delete=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('确定要删除这个优惠码吗？');">删除</a>
                      <?php else: ?>
                        <span class="text-muted">不可操作</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="8" class="text-center">暂无优惠码</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      
      <!-- 批量生成优惠码模态框 -->
      <div class="modal fade" id="generateModal" tabindex="-1" role="dialog" aria-labelledby="generateModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title" id="generateModalLabel">批量生成优惠码</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form method="post">
                <input type="hidden" name="action" value="generate">
                
                <div class="form-group">
                  <label for="count">生成数量</label>
                  <input type="number" class="form-control" id="count" name="count" min="1" max="100" value="10" required>
                </div>
                
                <div class="form-group">
                  <label for="amount">优惠金额</label>
                  <div class="input-group">
                    <div class="input-group-prepend">
                      <span class="input-group-text">¥</span>
                    </div>
                    <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" value="10.00" required>
                  </div>
                </div>
                
                <div class="form-group">
                  <label for="prefix">优惠码前缀（可选）</label>
                  <input type="text" class="form-control" id="prefix" name="prefix" maxlength="5" placeholder="例如：VIP-">
                  <small class="form-text text-muted">前缀将添加到随机生成的优惠码前面</small>
                </div>
                
                <button type="submit" class="btn btn-primary">生成优惠码</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- 引入 Bootstrap 4 JS 和依赖 -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
</body>
</html>