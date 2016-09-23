/*
    Adicionar estas tabelas no banco que deseja fazer o Backup
*/

CREATE TABLE IF NOT EXISTS `backupconf` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intervalo` int(11) DEFAULT NULL COMMENT 'intervalo de tempo em dias',
  `unidadeintervalo` enum('SEGUNDO','MINUTO','HORA','DIA','SEMANA','MES','ANO','MINUTO','HORAS','DIA','SEMANA','MES','ANO') DEFAULT NULL,
  `emails` text COMMENT 'emails que receberam uma cópia do backup',
  `ativo` enum('SIM',' NAO') DEFAULT 'SIM' COMMENT 'se o sistema de back está ativo',
  `dataedicao` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8 COMMENT='configurações do Backup';

DELETE FROM `backupconf`;
/*!40000 ALTER TABLE `backupconf` DISABLE KEYS */;
INSERT INTO `backupconf` (`id`, `intervalo`, `unidadeintervalo`, `emails`, `ativo`, `dataedicao`) VALUES
	(2, 1, 'MINUTO', 'email1@mail.com, email2@mail.com, email3@mail.com', 'SIM', '2016-09-23 08:14:59');
/*!40000 ALTER TABLE `backupconf` ENABLE KEYS */;

CREATE TABLE IF NOT EXISTS `backuplog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `arquivo` varchar(255) DEFAULT NULL,
  `emails` text,
  `periodo` varchar(255) DEFAULT NULL,
  `datacadastro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;

