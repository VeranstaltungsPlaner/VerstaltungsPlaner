<?php

    include_once __DIR__.'/../class/projectWeek.php';
    include_once __DIR__.'/../class/projectWeekEntry.php';
    include_once __DIR__.'/../class/eventRegistration.php';
    include_once __DIR__.'/../class/changePhaseMessage.php';
    include_once __DIR__.'/../class/blockedUserCollection.php';

    /**
     * Class PhaseManager
     */
    class PhaseManager {

        private $databaseHandler;
        private $projectWeek;

        /**
         * PhaseManager constructor.
         * @param ProjectWeek $projectWeek
         */
        public function __construct($projectWeek) {

            $this->databaseHandler = new PDOHandler();
            $this->projectWeek = $projectWeek;
        }

        /**
         * Phasenwechsel einleiten.
         * @param $newPhase
         * @return ChangePhaseMessage|null
         */
        public function changePhase($newPhase) {
            if($newPhase == 2) {
                return $this->changeToPhaseTwo();
            } else if($newPhase == 3) {
                return $this->changeToPhaseThree();
            }
        }

        /**
         * Phasenwechsel von der ersten zur zweiten Phase.
         * @return ChangePhaseMessage
         */
        private function changeToPhaseTwo() {

            // benoetigte Veranstaltungsplaetze
            $cntUsers = $this->getUserCount();

            // Alle Projektwochen-Eintraege
            $projectWeekEntries = $this->projectWeek->getProjectWeekEntries();

            for($position = 1; $position <= 10; $position++) {

                $cntUserSpace = 0;

                foreach($projectWeekEntries as $projectWeekEntry) {

                    if($projectWeekEntry->getPosition() == $position
                        || ($projectWeekEntry->getPosition() < $position
                            && ($projectWeekEntry->getPosition() + $projectWeekEntry->getEvent()->length - 1) >= $position)){

                        $cntUserSpace += $projectWeekEntry->getMaxParticipants();

                    } else if($projectWeekEntry->getPosition() < $position) {
                        $projectWeekEntries = $this->unsetValue($projectWeekEntries, $projectWeekEntry);
                    }
                }

                // Sind nicht genuegend Veranstaltungsplaetze auf einer Position vorhanden,
                // wird eine Fehlermeldung ausgegeben und der Phasenwechsel wird abgebrochen.
                if($cntUserSpace < $cntUsers) {
                    return new ChangePhaseMessage(false, 1, $position, ($cntUsers - $cntUserSpace));
                }
            }

            // Phasenwechsel speichern und eine Erfolgsmeldung zurueckgeben.
            $this->savePhaseChange(2);
            return new ChangePhaseMessage(true, 2);
        }

        /**
         * Phasenwechsel von der zweiten zur dritten Phase.
         * @return ChangePhaseMessage
         */
        private function changeToPhaseThree() {

            $blockedUserCollection = new BlockedUserCollection();

            for($position = 1; $position <= 10; $position++) {

                $users = $this->getAllUsers();
                $projectWeekEntries = $this->projectWeek->getProjectWeekEntriesAtPosition($position);

                foreach ($projectWeekEntries as $projectWeekEntry) {

                    // laden aller Registrierungen zu einem Projektwochen-Eintrag
                    // - absteigend Sortiert nach der Prioritaet der Registrierung
                    $registrations = $this->getRegistrationsOfProjectWeekEntry($projectWeekEntry->getId());

                    // Genehmigung aller moeglichen Registrierungen
                    for($i = 0; $i < $projectWeekEntry->getMaxParticipants(); $i++) {

                        if(count($registrations) != 0) {

                            // Registrierung mit hoechster Prioritaet
                            $firstRegistration = $registrations[0];

                            // den registrierten Mitarbeiter aus dem Benutzer-Array entfernenen.
                            $approvedUsername = $firstRegistration->getUsername();
                            $users = $this->removeUserOfUserArray($users, $approvedUsername);

                            // Mitarbeiter nicht blockiert, Registrierung wird bestaetigt.
                            if(!$blockedUserCollection->exists($approvedUsername)) {

                                $this->approveRegistration($firstRegistration);

                                // Falls die Veranstaltung laenger als ein Halbtag ist,
                                // wird der Mitarbeiter fuer die folgenden Zuweisungen gesperrt.
                                $eventLength = $firstRegistration->getProjectWeekEntry()->getEvent()->length;
                                if($eventLength > 1) {
                                    $blockedUserCollection->add(new BlockedUser($approvedUsername, $eventLength));
                                }

                                $this->increaseParticipantCount($projectWeekEntry);
                            }

                            // entfernen der behandelten Registrierungen.
                            $registrations = $this->unsetValue($registrations, $firstRegistration);

                        } else {
                            break;
                        }
                    }
                }

                // Zuweisung der fehlenden Mitarbeiter zur aktuellen Position
                if(count($users) != 0) {

                    $unfilledProjectWeekEntries = $this->getUnfilledProjectWeekEntriesAtPosition($this->projectWeek, $position);

                    foreach($unfilledProjectWeekEntries as $unfilledProjectWeekEntry) {

                        // Solange die Veranstaltung nicht vollstaendig gefuellt ist,
                        // werden (falls vorhanden) weitere Mitarbeiter eingeschrieben.
                        for($i = $unfilledProjectWeekEntry->getParticipants(); $i < $unfilledProjectWeekEntry->getMaxParticipants(); $i++) {

                            if(count($users) == 0) {
                                break;
                            }

                            $firstUser = $users[0];

                            // Mitarbeiter nicht blockiert, Registrierung wird bestaetigt.
                            if(!$blockedUserCollection->exists($firstUser)) {

                                $this->createEventRegistration($firstUser, $unfilledProjectWeekEntry);
                                $this->increaseParticipantCount($unfilledProjectWeekEntry);

                            } else {
                                // Schleifendurchgang mit einem anderen Mitarbeiter erneut durchfuehren
                                $i--;
                            }

                            $users = $this->unsetValue($users, $users[0]);
                        }

                        if(count($users) == 0) {
                            break;
                        }
                    }
                }

                $blockedUserCollection->decreaseCount();
            }

            // Phasenwechsel speichern und Status zurueckgeben
            $this->savePhaseChange(3);
            return new ChangePhaseMessage(true, 3);
        }

        /**
         * Phasenwechsel speichern.
         * @param int $newPhase
         */
        private function savePhaseChange($newPhase) {
            $this->projectWeek->setPhase($newPhase);
            $this->projectWeek->save();
        }

        /**
         * Registrierung genehmigen und speichern.
         * @param EventRegistration $registration
         */
        private function approveRegistration($registration) {
            $registration->setApproved(1);
            $registration->save();
        }

        /**
         * Teilnehmer-Anzahl der Veranstaltung erhoehen.
         * @param ProjectWeekEntry $projectWeekEntry
         */
        private function increaseParticipantCount($projectWeekEntry, $num = 1) {
            $projectWeekEntry->setParticipants($projectWeekEntry->getParticipants() + $num);
            $projectWeekEntry->save();
        }

        /**
         * Entfernt einen Mitarbeiter aus einem Mitarbeiter-Array.
         * @param array $users
         * @param string $approvedUsername
         * @return array
         */
        private function removeUserOfUserArray($users, $approvedUsername) {
            foreach ($users as $user) {
                if ($user == $approvedUsername) {
                    $users = $this->unsetValue($users, $user);
                    break;
                }
            }
            return $users;
        }

        /**
         * @param array $array
         * @param $value
         * @param bool $strict
         * @return array
         */
        private function unsetValue(array $array, $value, $strict = TRUE) {
            if(($key = array_search($value, $array, $strict)) !== FALSE) {
                unset($array[$key]);
            }

            $newArray = [];

            foreach($array as $entry) {
                array_push($newArray, $entry);
            }

            return $newArray;
        }

        /**
         * Liefert die Anzahl der vorhandenen Mitarbeiter
         * @return int
         */
        private function getUserCount() {
            return $this->databaseHandler->count('User', 'name', 'personnalManager = 0');
        }

        /**
         * @return array
         */
        private function getAllUsers() {
            $users = [];

            $where = 'personnalManager = 0';
            $result = $this->databaseHandler->select('User', $where);

            foreach($result as $user) {
                array_push($users, $user['name']);
            }

            return $users;
        }

        /**
         * @param $projectWeekEntryId
         * @return array
         */
        private function getRegistrationsOfProjectWeekEntry($projectWeekEntryId) {
            $registrations = [];
            $where = 'projectWeekEntryId = '.$projectWeekEntryId.' ORDER BY priority DESC';
            $result = $this->databaseHandler->select('EventRegistration', $where);

            foreach($result as $eventRegistration) {
                array_push($registrations, new EventRegistration($eventRegistration['eventRegistrationId']));
            }

            return $registrations;
        }

        /**
         * @param $projectWeek
         * @param $position
         * @return array
         */
        private function getUnfilledProjectWeekEntriesAtPosition($projectWeek, $position) {
            $projecWeekEntries = [];
            $sqlString = 'SELECT * FROM ProjectWeekEntry WHERE year = '.$projectWeek->getYear().' AND week = '.$projectWeek->getWeek().' AND position = '.$position.' AND participants < maxParticipants;';

            $result = $this->databaseHandler->query($sqlString);

            foreach($result as $projectWeekEntry) {
                array_push($projecWeekEntries, new ProjectWeekEntry($projectWeekEntry['projectWeekEntryId']));
            }

            return $projecWeekEntries;
        }

        /**
         * @param $username
         * @param $projectWeekEntry
         */
        private function createEventRegistration($username, $projectWeekEntry) {
            $eventRegistration = new EventRegistration();
            $eventRegistration->setUsername($username);
            $eventRegistration->setProjectWeekEntry($projectWeekEntry);
            $eventRegistration->setPriority(1);
            $eventRegistration->setApproved(1);
            $eventRegistration->setRegistrationDate(date('Y-m-d H:i:s'));
            $eventRegistration->save();
        }
    }

?>