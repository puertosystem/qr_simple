<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="index.php" class="brand-link">
      <span class="brand-text font-weight-light">Certificados QR</span>
    </a>
    <div class="sidebar">
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <?php if (!empty($_SESSION['user_profile_image'])): ?>
            <img src="<?php echo $_SESSION['user_profile_image']; ?>" class="img-circle elevation-2" alt="User Image" style="width:32px;height:32px;object-fit:cover;">
          <?php else: ?>
            <img src="assets/lte/dist/img/avatar5.png" class="img-circle elevation-2" alt="User Image">
          <?php endif; ?>
        </div>
        <div class="info">
          <a href="#" class="d-block"><?php echo $_SESSION['user_name'] ?? 'Usuario'; ?></a>
        </div>
      </div>
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo (!isset($_GET['page']) || $_GET['page'] === 'home') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-home"></i>
              <p>Inicio</p>
            </a>
          </li>
          <li class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] === 'participants') ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] === 'participants') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-user-friends"></i>
              <p>
                Participantes
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?page=participants" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] === 'participants' && !isset($_GET['view'])) ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Listado</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?page=participants&view=create" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] === 'participants' && isset($_GET['view']) && $_GET['view'] === 'create') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Nuevo Participante</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item <?php echo (isset($_GET['page']) && ($_GET['page'] === 'courses' || $_GET['page'] === 'auspices')) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (isset($_GET['page']) && ($_GET['page'] === 'courses' || $_GET['page'] === 'auspices')) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-book"></i>
              <p>
                Cursos
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?page=courses" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] === 'courses' && !isset($_GET['view'])) ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Listado</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?page=courses&view=create" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] === 'courses' && isset($_GET['view']) && $_GET['view'] === 'create') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Nuevo Curso</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?page=auspices" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] === 'auspices') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Auspicios</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
            <a href="index.php?page=certificates" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] === 'certificates') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-qrcode"></i>
              <p>Certificados</p>
            </a>
          </li>
          <li class="nav-item <?php echo (isset($_GET['page']) && ($_GET['page'] === 'settings' || $_GET['page'] === 'users')) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (isset($_GET['page']) && ($_GET['page'] === 'settings' || $_GET['page'] === 'users')) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-cogs"></i>
              <p>
                Ajustes
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="index.php?page=settings" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] === 'settings') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Versión y Licencia</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?page=users" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] === 'users') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Gestión de Usuarios</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="index.php?page=updates" class="nav-link <?php echo (isset($_GET['page']) && $_GET['page'] === 'updates') ? 'active' : ''; ?>">
                  <i class="fas fa-sync-alt nav-icon"></i>
                  <p>Actualización</p>
                </a>
              </li>
            </ul>
          </li>
        </ul>
      </nav>
    </div>
  </aside>
