class nginx {
  package {
    'nginx-full':
      ensure => present
  }

  service {
    'nginx':
      enable  => true,
      ensure  => running,
      require => Package['nginx-full']
  }

  exec {
    'backup-nginx-conf':
      command => 'cp -r /etc/nginx /etc/nginx.bak',
      creates => '/etc/nginx.bak/nginx.conf',
      require => Package['nginx-full'];
    'enable-site-www-conf':
      command => 'rm -rf * && ln -s ../sites-available/www.conf',
      creates => '/etc/nginx/sites-enabled/www.conf',
      cwd     => '/etc/nginx/sites-enabled',
      user    => 'root';
  }

  file {
    '/etc/nginx/sites-available/www.conf':
      content  => template("nginx/www-${env}.erb"),
      notify  => Exec['enable-site-www-conf'];
    '/etc/nginx/nginx.conf':
      content  => template("nginx/nginx-${env}.erb"),
      owner   => 'root',
      group   => 'root',
      notify  => Service['nginx'];
    '/etc/nginx/upstream_phpcgi_tcp.conf':
      content  => template("nginx/upstream_phpcgi_tcp-${env}.erb"),
      owner   => 'root',
      group   => 'root',
      notify  => Service['nginx'];
    '/var/cache/nginx':
      ensure => directory,
      owner   => 'root',
      group   => 'root',
      require => File['/etc/nginx/nginx.conf'],
      notify  => Service['nginx'];
  }

}

