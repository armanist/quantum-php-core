<?php

namespace Quantum\Tests\Unit\Libraries\Database\Adapters\Idiorm;

use Quantum\Libraries\Database\Adapters\Idiorm\IdiormDbal;
use Quantum\Libraries\Database\Database;
use Quantum\Tests\Unit\AppTestCase;

abstract class IdiormDbalTestCase extends AppTestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->setPrivateProperty(Database::class, 'instance', null);

        config()->set('debug', true);

        IdiormDbal::connect(['driver' => 'sqlite', 'database' => ':memory:']);

        $this->_createUserTableWithData();

        $this->_createEventsTableWithData();

        $this->_createUserEventTableWithData();

        $this->_createProfessionTableWithData();

        $this->_createMeetingsTableWithData();

        $this->_createTicketsTableWithData();
    }

    public function tearDown(): void
    {
        IdiormDbal::execute("DROP TABLE IF EXISTS users");

        IdiormDbal::execute("DROP TABLE IF EXISTS events");

        IdiormDbal::execute("DROP TABLE IF EXISTS user_events");

        IdiormDbal::execute("DROP TABLE IF EXISTS user_professions");

        IdiormDbal::execute("DROP TABLE IF EXISTS meetings");

        IdiormDbal::execute("DROP TABLE IF EXISTS tickets");

        IdiormDbal::disconnect();
    }

    private function _createUserTableWithData()
    {
        IdiormDbal::execute("CREATE TABLE IF NOT EXISTS users (
                        id INTEGER PRIMARY KEY,
                        firstname VARCHAR(255),
                        lastname VARCHAR(255),
                        age INTEGER(11),
                        country VARCHAR(255),
                        created_at DATETIME
                    )");

        IdiormDbal::execute("INSERT INTO 
                    users
                        (firstname, lastname, age, country, created_at) 
                    VALUES
                        ('John', 'Doe', 45, 'Ireland', '2020-01-04 20:28:33'), 
                        ('Jane', 'Du', 35, 'England', '2020-02-14 10:15:12')
                    ");
    }

    private function _createEventsTableWithData()
    {
        IdiormDbal::execute("CREATE TABLE IF NOT EXISTS events (
                        id INTEGER PRIMARY KEY,
                        title VARCHAR(255),
                        country VARCHAR(255),
                        started_at DATETIME
                    )");

        IdiormDbal::execute("INSERT INTO 
                    events
                        (title, country, started_at) 
                    VALUES
                        ('Dance', 'New Zealand', '2019-01-04 20:28:33'), 
                        ('Music', 'England', '2019-09-14 10:15:12'),
                        ('Design', 'Ireland', '2020-02-14 10:15:12'),
                        ('Music', 'Ireland', '2035-09-14 10:15:12'),
                        ('Film', 'Ireland', '2040-02-14 10:15:12'),
                        ('Art', 'Island', NULL),
                        ('Music', 'Island', NULL)
                    ");
    }

    private function _createUserEventTableWithData()
    {
        IdiormDbal::execute("CREATE TABLE IF NOT EXISTS user_events (
                        id INTEGER PRIMARY KEY,
                        user_id INTEGER(11),
                        event_id INTEGER(11),
                        confirmed VARCHAR(3), 
                        created_at DATETIME
                    )");

        IdiormDbal::execute("INSERT INTO 
                    user_events
                        (user_id, event_id, confirmed, created_at) 
                    VALUES
                        (1, 1, 'Yes', '2020-01-04 20:28:33'),
                        (1, 2, 'No', '2020-02-19 05:15:12'),
                        (1, 7, 'No', '2020-02-22 11:15:15'),
                        (2, 2, 'Yes', '2020-03-10 02:17:12'),
                        (2, 3, 'No', '2020-04-17 12:25:18'),
                        (2, 5, 'No', '2020-04-15 11:10:12'),
                        (100, 200, 'No', '2020-04-15 11:10:12'),
                        (110, 220, 'No', '2020-04-15 11:10:12')
                    ");
    }

    private function _createProfessionTableWithData()
    {
        IdiormDbal::execute("CREATE TABLE IF NOT EXISTS user_professions (
                        id INTEGER PRIMARY KEY,
                        user_id INTEGER(11),
                        title VARCHAR(255)
                    )");

        IdiormDbal::execute("INSERT INTO 
                    user_professions
                        (user_id, title) 
                    VALUES
                        (1, 'Writer'), 
                        (2, 'Singer')
                    ");
    }

    private function _createMeetingsTableWithData()
    {
        IdiormDbal::execute("CREATE TABLE IF NOT EXISTS meetings (
                        id INTEGER PRIMARY KEY,
                        user_id INTEGER (11),
                        title VARCHAR (255),
                        start_date DATETIME
        )");

        IdiormDbal::execute("INSERT INTO 
                        meetings 
                            (user_id, title, start_date)
                        VALUES 
                            (1, 'Business planning', '2021-11-01 11:00:00'),
                            (1, 'Business management', '2021-11-05 11:00:00'),
                            (2, 'Marketing', '2021-11-10 13:00:00')
        ");
    }

    private function _createTicketsTableWithData()
    {
        IdiormDbal::execute("CREATE TABLE IF NOT EXISTS tickets (
                        id INTEGER PRIMARY KEY,
                        meeting_id INTEGER (11),
                        type VARCHAR (255),
                        number VARCHAR (255) 
        )");

        IdiormDbal::execute("INSERT INTO 
                        tickets 
                            (meeting_id, type, number)
                        VALUES 
                            (1, 'regular', 'R1245'),
                            (1, 'regular', 'R4563'),
                            (1, 'vip', 'V4563'),
                            (2, 'vip', 'V7854'),
                            (2, 'vip', 'V7410')
        ");
    }
}
