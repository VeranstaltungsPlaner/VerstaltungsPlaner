-- MySQL Script generated by MySQL Workbench
-- Sun Oct  8 02:08:38 2017
-- Model: New Model    Version: 1.0
-- MySQL Workbench Forward Engineering

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL,ALLOW_INVALID_DATES';

-- -----------------------------------------------------
-- Schema vstp
-- -----------------------------------------------------

-- -----------------------------------------------------
-- Schema vstp
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `vstp` DEFAULT CHARACTER SET utf8 ;
USE `vstp` ;

-- -----------------------------------------------------
-- Table `vstp`.`User`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vstp`.`User` (
  `name` VARCHAR(12) NOT NULL,
  `password` VARCHAR(45) NULL,
  `personnalManager` TINYINT NULL,
  `email` VARCHAR(45) NULL,
  PRIMARY KEY (`name`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `vstp`.`Event`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vstp`.`Event` (
  `eventId` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NULL,
  `description` LONGTEXT NULL,
  `length` INT NULL,
  `maxParticipants` INT NULL,
  `eventManager` VARCHAR(12) NOT NULL,
  PRIMARY KEY (`eventId`),
  INDEX `fk_Event_User1_idx` (`eventManager` ASC),
  CONSTRAINT `fk_Event_User1`
    FOREIGN KEY (`eventManager`)
    REFERENCES `vstp`.`User` (`name`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `vstp`.`ProjectWeek`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vstp`.`ProjectWeek` (
  `year` INT NOT NULL,
  `week` INT NOT NULL,
  `from` DATETIME NULL,
  `until` DATETIME NULL,
  PRIMARY KEY (`year`, `week`))
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `vstp`.`ProjectWeekEntry`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vstp`.`ProjectWeekEntry` (
  `projectWeekEntryId` INT NOT NULL AUTO_INCREMENT,
  `eventId` INT NOT NULL,
  `year` INT NOT NULL,
  `week` INT NOT NULL,
  `position` INT NOT NULL,
  `participants` INT NOT NULL,
  `maxParticipants` INT NOT NULL,
  PRIMARY KEY (`projectWeekEntryId`),
  INDEX `fk_ProjectWeekEntry_Event1_idx` (`eventId` ASC),
  INDEX `fk_ProjectWeekEntry_ProjectWeek1_idx` (`year` ASC, `week` ASC),
  CONSTRAINT `fk_ProjectWeekEntry_Event1`
    FOREIGN KEY (`eventId`)
    REFERENCES `vstp`.`Event` (`eventId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_ProjectWeekEntry_ProjectWeek1`
    FOREIGN KEY (`year` , `week`)
    REFERENCES `vstp`.`ProjectWeek` (`year` , `week`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `vstp`.`EventRegistration`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `vstp`.`EventRegistration` (
  `eventRegistrationId` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(12) NOT NULL,
  `projectWeekEntryId` INT NOT NULL,
  `year` INT NOT NULL,
  `week` INT NOT NULL,
  `priority` INT NULL DEFAULT 1,
  `approved` TINYINT NULL,
  `registrationDate` DATETIME NULL,
  PRIMARY KEY (`eventRegistrationId`, `username`, `projectWeekEntryId`),
  INDEX `fk_BenutzerProVeranstaltung_Benutzer1_idx` (`username` ASC),
  INDEX `fk_UserPerEvent_ProjectWeekEntry1_idx` (`projectWeekEntryId` ASC),
  INDEX `fk_EventRegistration_ProjectWeek1_idx` (`year` ASC, `week` ASC),
  CONSTRAINT `fk_BenutzerProVeranstaltung_Benutzer1`
    FOREIGN KEY (`username`)
    REFERENCES `vstp`.`User` (`name`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_UserPerEvent_ProjectWeekEntry1`
    FOREIGN KEY (`projectWeekEntryId`)
    REFERENCES `vstp`.`ProjectWeekEntry` (`projectWeekEntryId`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_EventRegistration_ProjectWeek1`
    FOREIGN KEY (`year` , `week`)
    REFERENCES `vstp`.`ProjectWeek` (`year` , `week`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;

CREATE USER 'Admin';

GRANT ALL ON `vstp`.* TO 'Admin';
CREATE USER 'PersonnalManager';

GRANT SELECT, INSERT, TRIGGER ON TABLE `vstp`.* TO 'PersonnalManager';
CREATE USER 'Employee';

GRANT SELECT ON TABLE `vstp`.* TO 'Employee';
GRANT SELECT, INSERT, TRIGGER, UPDATE, DELETE ON TABLE `vstp`.* TO 'Employee';

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
