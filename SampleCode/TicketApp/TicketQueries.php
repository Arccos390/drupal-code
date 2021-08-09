<?php

namespace Drupal\ticketapp\Query;

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class TicketQueries.
 *
 * @package Drupal\ticketapp\Query
 */
class TicketQueries {

  public const RESULT_TYPE_COUNTER = 'counter';

  public const RESULT_TYPE_MONEY = 'money';

  /*
   * RESULT_TYPE_COUNTER__COUNT is similar to RESULT_TYPE_COUNTER but
   * also defines that `COUNT(*)` should be used for the query.
   *
   * Please take into account that not every function in this class supports
   * this constant.
   */
  public const RESULT_TYPE_COUNTER__COUNT = 'counter_count';

  /*
   * RESULT_TYPE_COUNTER__SUM is similar to RESULT_TYPE_COUNTER but also defines
   * that `SUM(qty)` should be used for the query.
   *
   * Please take into account that not every function in this class supports
   * this constant.
   */
  public const RESULT_TYPE_COUNTER__SUM = 'counter_sum';

  /**
   * Returns the terminals of a specific timetable node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $timetable
   *   The timetable node to check for terminals.
   *
   * @return array
   *   An array containing the terminal ids as values.
   */
  public static function getTerminals(EntityInterface $timetable): array {
    $terminals = &drupal_static(__FUNCTION__);
    if (!isset($terminals[$timetable->id()])) {
      $terminals[$timetable->id()] = [];
      $query = \Drupal::database()->select('ticket', 'ticket');
      $query->innerJoin('node_field_data', 'terminal', 'terminal.nid = ticket.terminal');
      $query->distinct(TRUE);
      $query->fields('ticket', ['terminal']);
      $query->fields('terminal', ['title']);
      $query->condition('ticket.timetable', $timetable->id());
      $query->orderBy('ticket.timetable');
      $terminals[$timetable->id()] = $query->execute()->fetchAllKeyed();
    }
    return $terminals[$timetable->id()];
  }

  /**
   * Returns a timestamp with the date of the last ticket created.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   *
   * @return int
   *   The unix timestamp retrieved from database.
   */
  public static function getLastAddedDate($timetable): int {
    $query = \Drupal::database()->select('ticket', 't')
      ->fields('t', ['changed'])
      ->condition('t.timetable', $timetable)
      ->range(0, 1)
      ->orderBy('t.changed', 'DESC');
    return $query->execute()->fetchField();
  }

  /**
   * Returns the capacity of a ship.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   *
   * @return int
   *   The capacity of the ship in persons.
   */
  public static function getCapacityPersons($timetable): int {
    $node = Node::load($timetable);
    if ($ship_id = $node->get('field_timetable_ship')->target_id) {
      if ($ship = Node::load($ship_id)) {
        if ($capacity = $ship->get('field_ship_people')->value) {
          return $capacity;
        }
      }
    }
    return 0;
  }

  /**
   * Returns the total number of people.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of total tickets returned.
   */
  public static function getTicketPeople($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    switch ($type) {
      case self::RESULT_TYPE_COUNTER:
        return self::getTicketPeopleWhole($timetable, $type, $terminal) +
          self::getTicketPeopleReduced($timetable, $type, $terminal) +
          // Groups and families should return results equivalent to what
          // would be returned by passing self::RESULT_TYPE_COUNTER__SUM.
          self::getTicketPeopleGroup($timetable, self::RESULT_TYPE_COUNTER__SUM, $terminal) +
          self::getTicketPeopleGroupSchool($timetable, self::RESULT_TYPE_COUNTER__SUM, $terminal) +
          self::getTicketPeopleFamily($timetable, self::RESULT_TYPE_COUNTER__SUM, $terminal);

      case self::RESULT_TYPE_COUNTER__COUNT:
      case self::RESULT_TYPE_COUNTER__SUM:
        return self::getTicketPeopleWhole($timetable, self::RESULT_TYPE_COUNTER, $terminal) +
          self::getTicketPeopleReduced($timetable, self::RESULT_TYPE_COUNTER, $terminal) +
          // Groups and families should return results equivalent to what
          // would be returned by passing self::RESULT_TYPE_COUNTER__SUM.
          self::getTicketPeopleGroup($timetable, $type, $terminal) +
          self::getTicketPeopleGroupSchool($timetable, $type, $terminal) +
          self::getTicketPeopleFamily($timetable, $type, $terminal);

      case self::RESULT_TYPE_MONEY:
        return self::getTicketPeopleWhole($timetable, $type, $terminal) +
          self::getTicketPeopleReduced($timetable, $type, $terminal) +
          // Groups and families should return results equivalent to what
          // would be returned by passing self::RESULT_TYPE_COUNTER__SUM.
          self::getTicketPeopleGroup($timetable, $type, $terminal) +
          self::getTicketPeopleGroupSchool($timetable, $type, $terminal) +
          self::getTicketPeopleFamily($timetable, $type, $terminal);
    }
    return 0;
  }

  /**
   * Returns the number of whole tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of whole tickets returned.
   */
  public static function getTicketPeopleWhole($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.bundle = :bundle AND dt.entity_id = t.document', [
            ':bundle' => 'documents',
          ]);
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__PERSONS_WHOLLY, 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__PERSONS_WHOLLY, 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $query->addExpression('IFNULL(SUM(total), 0)', 'counter');
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of reduced tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of reduced tickets returned.
   */
  public static function getTicketPeopleReduced($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__PERSONS_REDUCED, 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__PERSONS_REDUCED, 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $query->addExpression('IFNULL(SUM(total), 0)', 'counter');
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of baby tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of baby tickets returned.
   */
  public static function getTicketBaby($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', 'passenger_baby', 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', 'passenger_baby', 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $query->addExpression('IFNULL(SUM(total), 0)', 'counter');
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of group tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of group tickets returned.
   */
  public static function getTicketPeopleGroup($timetable, $type = self::RESULT_TYPE_COUNTER__COUNT, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
        case self::RESULT_TYPE_COUNTER__COUNT:
        case self::RESULT_TYPE_COUNTER__SUM:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__PERSONS_GROUP, 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          if ($type === self::RESULT_TYPE_COUNTER__SUM) {
            // In the case of group tickets we don't have a different record per
            // ticket but a single ticket with quantity column filled.
            $query->addExpression('IFNULL(SUM(qty), 0)', 'counter');
          }
          else {
            $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          }
          // Addition with 0 causes unnecessary decimals coming from database to
          // disappear.
          $timetables[$cid] = 0 + $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__PERSONS_GROUP, 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $query->addExpression('IFNULL(SUM(total), 0)', 'counter');
          // Addition with 0 causes unnecessary decimals coming from database to
          // disappear.
          $timetables[$cid] = 0 + $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of group school tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of group tickets returned.
   */
  public static function getTicketPeopleGroupSchool($timetable, $type = self::RESULT_TYPE_COUNTER__COUNT, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
        case self::RESULT_TYPE_COUNTER__COUNT:
        case self::RESULT_TYPE_COUNTER__SUM:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__PERSONS_GROUP_SCHOOL, 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          if ($type === self::RESULT_TYPE_COUNTER__SUM) {
            // In the case of group tickets we don't have a different record per
            // ticket but a single ticket with quantity column filled.
            $query->addExpression('IFNULL(SUM(qty), 0)', 'counter');
          }
          else {
            $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          }
          // Addition with 0 causes unnecessary decimals coming from database to
          // disappear.
          $timetables[$cid] = 0 + $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__PERSONS_GROUP_SCHOOL, 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $query->addExpression('IFNULL(SUM(total), 0)', 'counter');
          // Addition with 0 causes unnecessary decimals coming from database to
          // disappear.
          $timetables[$cid] = 0 + $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of family tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of family tickets returned.
   */
  public static function getTicketPeopleFamily($timetable, $type = self::RESULT_TYPE_COUNTER__COUNT, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
        case self::RESULT_TYPE_COUNTER__COUNT:
        case self::RESULT_TYPE_COUNTER__SUM:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__PERSONS_FAMILY, 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          if ($type === self::RESULT_TYPE_COUNTER__SUM) {
            // In the case of group tickets we don't have a different record per
            // ticket but a single ticket with quantity column filled.
            $query->addExpression('IFNULL(SUM(qty), 0)', 'counter');
          }
          else {
            $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          }
          // Addition with 0 causes unnecessary decimals coming from database to
          // disappear.
          $timetables[$cid] = 0 + $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__PERSONS_FAMILY, 'IN');
          $query->condition('t.timetable', $timetable);
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $query->addExpression('IFNULL(SUM(total), 0)', 'counter');
          // Addition with 0 causes unnecessary decimals coming from database to
          // disappear.
          $timetables[$cid] = 0 + $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the total number of car tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of car tickets returned.
   */
  public static function getTicketCars($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    switch ($type) {
      case self::RESULT_TYPE_COUNTER:
      case self::RESULT_TYPE_MONEY:
        return self::getTicketCarsSmall($timetable, $type, $terminal) +
          self::getTicketCarsBig($timetable, $type, $terminal);
    }
    return 0;
  }

  /**
   * Returns the number of big-car tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of big car tickets returned.
   */
  public static function getTicketCarsBig($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__CAR_BIG, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__CAR_BIG, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }

    return $timetables[$cid];
  }

  /**
   * Returns the number of small car tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of small car tickets returned.
   */
  public static function getTicketCarsSmall($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__CAR_SMALL, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__CAR_SMALL, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the total number of bike tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The total number of bike tickets returned.
   */
  public static function getTicketBikes($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    switch ($type) {
      case self::RESULT_TYPE_COUNTER:
        $query = \Drupal::database()->select('ticket', 't');
        $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
        $query->condition('t.timetable', $timetable);
        $or = $query->orConditionGroup();
        $or->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BIKE_BIG, 'IN');
        $or->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BIKE_SMALL, 'IN');
        $or->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BICYCLE, 'IN');
        $query->condition($or);
        $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
        if ($terminal) {
          $query->condition('t.terminal', $terminal);
        }
        return $query->execute()->fetchField();

      case self::RESULT_TYPE_MONEY:
        $query = \Drupal::database()->select('ticket', 't');
        $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
        $query->condition('t.timetable', $timetable);
        $or = $query->orConditionGroup();
        $or->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BIKE_BIG, 'IN');
        $or->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BIKE_SMALL, 'IN');
        $or->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BICYCLE, 'IN');
        $query->condition($or);
        $query->addExpression('IFNULL(SUM(total), 0)', 'counter');
        if ($terminal) {
          $query->condition('t.terminal', $terminal);
        }
        return $query->execute()->fetchField();
    }
    return 0;
  }

  /**
   * Returns the number of big bikes tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of big bikes tickets returned.
   */
  public static function getTicketBikesBig($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BIKE_BIG, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BIKE_BIG, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of small bike tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of small bike tickets returned.
   */
  public static function getTicketBikesSmall($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BIKE_SMALL, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BIKE_SMALL, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of Bicycle tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of ATV bikes tickets returned.
   */
  public static function getTicketBicycles($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BICYCLE, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BICYCLE, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of bus tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of bus tickets returned.
   */
  public static function getTicketBus($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BUS, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__BUS, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the total number of truck tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The total number of truck tickets returned.
   */
  public static function getTicketTrucks($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    switch ($type) {
      case self::RESULT_TYPE_COUNTER:
      case self::RESULT_TYPE_MONEY:
        return self::getTicketTrucks5m($timetable, $type, $terminal) +
          self::getTicketTrucks8m($timetable, $type, $terminal) +
          self::getTicketTrucks9m($timetable, $type, $terminal) +
          self::getTicketTrucks10m($timetable, $type, $terminal) +
          self::getTicketTrucks12m($timetable, $type, $terminal) +
          self::getTicketTrucks12mp($timetable, $type, $terminal);
    }
    return 0;
  }

  /**
   * Returns the number of trucks < 5m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of trucks < 5m tickets returned.
   */
  public static function getTicketTrucks5m($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 5, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 5, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of trucks < 8m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of trucks < 8m tickets returned.
   */
  public static function getTicketTrucks8m($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 5, '>');
          $query->condition('p.field_plate_meters_value', 8, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 5, '>');
          $query->condition('p.field_plate_meters_value', 8, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of trucks < 9m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of trucks < 9m tickets returned.
   */
  public static function getTicketTrucks9m($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 8, '>');
          $query->condition('p.field_plate_meters_value', 9, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 8, '>');
          $query->condition('p.field_plate_meters_value', 9, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of trucks < 10m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of trucks < 10m tickets returned.
   */
  public static function getTicketTrucks10m($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 9, '>');
          $query->condition('p.field_plate_meters_value', 10, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 9, '>');
          $query->condition('p.field_plate_meters_value', 10, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of trucks < 12m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of trucks < 12m tickets returned.
   */
  public static function getTicketTrucks12m($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 10, '>');
          $query->condition('p.field_plate_meters_value', 12, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 10, '>');
          $query->condition('p.field_plate_meters_value', 12, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of trucks > 12m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of trucks > 12m tickets returned.
   */
  public static function getTicketTrucks12mp($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 12, '>');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK, 'IN');
          $query->condition('p.field_plate_meters_value', 12, '>');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of packs tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of packs tickets returned.
   */
  public static function getPacks($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', 'packs', 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', 'packs', 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of truck tank tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of truck tanks tickets returned.
   */
  public static function getTruckTank($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of tank < 5m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of tank < 5m tickets returned.
   */
  public static function getTruckTank5m($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 5, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 5, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of tank < 8m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of tank < 8m tickets returned.
   */
  public static function getTruckTank8m($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 5, '>');
          $query->condition('p.field_plate_meters_value', 8, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 5, '>');
          $query->condition('p.field_plate_meters_value', 8, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of tank < 9m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of tank < 9m tickets returned.
   */
  public static function getTruckTank9m($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 8, '>');
          $query->condition('p.field_plate_meters_value', 9, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 8, '>');
          $query->condition('p.field_plate_meters_value', 9, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of tank < 10m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of tank < 10m tickets returned.
   */
  public static function getTruckTank10m($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 9, '>');
          $query->condition('p.field_plate_meters_value', 10, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 9, '>');
          $query->condition('p.field_plate_meters_value', 10, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of tank < 12m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of tank < 12m tickets returned.
   */
  public static function getTruckTank12m($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 10, '>');
          $query->condition('p.field_plate_meters_value', 12, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 10, '>');
          $query->condition('p.field_plate_meters_value', 12, '<=');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of tank > 12m tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of tank > 12m tickets returned.
   */
  public static function getTruckTank12mp($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 12, '>');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->innerJoin('node__field_plate_meters', 'p', 'p.entity_id = t.plate');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__TRUCK_TANK, 'IN');
          $query->condition('p.field_plate_meters_value', 12, '>');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of tickets type municipality.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int
   *   The number of vehicle type municipality tickets returned.
   */
  public static function getMunicipality($timetable, $type = self::RESULT_TYPE_COUNTER, $terminal = NULL): int {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;

      switch ($type) {
        case self::RESULT_TYPE_COUNTER:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__VEHICLE_MUNICIPALITY, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(COUNT(*), 0)', 'counter');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;

        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->innerJoin('node__field_docs_type', 'dt', 'dt.entity_id = t.document');
          $query->condition('dt.field_docs_type_value', TICKETAPP_TICKET_TYPE__VEHICLE_MUNICIPALITY, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          $timetables[$cid] = $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of payed tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int|float
   *   The number of payed tickets returned.
   */
  public static function getTicketPayment($timetable, $type = self::RESULT_TYPE_MONEY, $terminal = NULL): float {
    switch ($type) {
      case self::RESULT_TYPE_MONEY:
        return self::getTicketPaymentCash($timetable, $type, $terminal) +
          self::getTicketPaymentCredit($timetable, $type, $terminal) +
          self::getTicketPaymentPos($timetable, $type, $terminal);
    }
    return 0;
  }

  /**
   * Returns the number of payed by cash tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int|float
   *   The number of payed by cash tickets returned.
   */
  public static function getTicketPaymentCash($timetable, $type = self::RESULT_TYPE_MONEY, $terminal = NULL) {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->condition('t.payment', TICKETAPP_TICKET_TYPE__PAYMENT_CASH, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->condition('t.status', 1);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          // Addition with 0 causes unnecessary decimals coming from database to
          // disappear.
          $timetables[$cid] = 0 + $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of payed by credit tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int|float
   *   The number of payed by credit tickets returned.
   */
  public static function getTicketPaymentCredit($timetable, $type = self::RESULT_TYPE_MONEY, $terminal = NULL) {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->condition('t.payment', TICKETAPP_TICKET_TYPE__PAYMENT_CREDIT, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->condition('t.status', 1);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          // Addition with 0 causes unnecessary decimals coming from database to
          // disappear.
          $timetables[$cid] = 0 + $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

  /**
   * Returns the number of payed by POS tickets.
   *
   * @param int $timetable
   *   The nid of the timetable node.
   * @param string $type
   *   A result type defined as a const in this class.
   * @param int|null $terminal
   *   Limit the results of this function to a specific terminal.
   *
   * @return int|float
   *   The number of payed by POS tickets returned.
   */
  public static function getTicketPaymentPos($timetable, $type = self::RESULT_TYPE_MONEY, $terminal = NULL) {
    $timetables = &drupal_static(__METHOD__);
    $cid = implode(':', func_get_args());
    if (!isset($timetables[$cid])) {
      $timetables[$cid] = 0;
      switch ($type) {
        case self::RESULT_TYPE_MONEY:
          $query = \Drupal::database()->select('ticket', 't');
          $query->condition('t.payment', TICKETAPP_TICKET_TYPE__PAYMENT_POS, 'IN');
          $query->condition('t.timetable', $timetable);
          $query->condition('t.status', 1);
          $query->addExpression('IFNULL(SUM(total), 0)', 'total');
          if ($terminal) {
            $query->condition('t.terminal', $terminal);
          }
          // Addition with 0 causes unnecessary decimals coming from database to
          // disappear.
          $timetables[$cid] = 0 + $query->execute()->fetchField();
          break;
      }
    }
    return $timetables[$cid];
  }

}
