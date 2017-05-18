class php {
  package {
    'php7.0-fpm':
      ensure => present
  }

  service {
    'php7.0-fpm':
      enable  => true,
      ensure  => running,
      require => Package['php7.0-fpm']
  }

  exec {
    'backup-php-ini':
      command => 'cp -r /etc/php /etc/php.bak',
      creates => '/etc/php.bak/7.0/fpm/php.ini',
      require => Package['php7.0-fpm'];
  }

  file {
    '/etc/php/7.0/fpm/php.ini':
      source  => "puppet:///modules/php/php-${env}.ini",
      require => Package['php7.0-fpm'],
      notify  => Service['php7.0-fpm'];
  }

  if $env == 'dev' {
    file {
      '/etc/php/7.0/fpm/pool.d/www.conf':
      content => template("php/www-${env}.erb"),
      require => Package['php7.0-fpm'],
      notify  => Service['php7.0-fpm'];
    }
  }
  elsif $env == 'prod' {
    file {
      '/etc/php/7.0/fpm/pool.d/www1.conf':
        content => template("php/www-${env}-1.erb"),
        require => Package['php7.0-fpm'],
        notify  => Service['php7.0-fpm'];
      '/etc/php/7.0/fpm/pool.d/www2.conf':
        content => template("php/www-${env}-2.erb"),
        require => Package['php7.0-fpm'],
        notify  => Service['php7.0-fpm'];
    }
  }
}
