<?php

namespace Drupal\tablefiles\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\ticketapp_upload\Controller\Support;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TablePdf.
 *
 * @package Drupal\tablefiles\Controller
 */
class TablePdf extends ControllerBase {

  /**
   * The folder where to store the files.
   *
   * @var string
   */
  protected $directory = '/tablepdf';

  /**
   * The folder where to store files in the private folder.
   *
   * @var string
   */
  protected $publicDirectory;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The ticket handler service.
   *
   * @var \Drupal\tablefiles\Controller\TicketHandler
   */
  protected $ticketHandler;

  /**
   * TablePdf constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\File\FileSystem $file_system
   *   The file system service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $logger
   *   The logger channel.
   * @param \Drupal\tablefiles\Controller\TicketHandler $ticket_handler
   *   The ticket handler service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, FileSystem $file_system, LoggerChannelFactory $logger, TicketHandler $ticket_handler) {
    $this->entityManager = $entity_type_manager;
    $this->fileSystem = $file_system;
    $this->logger = $logger->get('ticketapp_tablepdf');
    $this->ticketHandler = $ticket_handler;
    $this->publicDirectory = 'private://ticketapp_tablepdf/' . date('Y-m');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('logger.factory'),
      $container->get('ticketapp.tablefiles.ticket_handler')
    );
  }

  /**
   * Generates the PDF.
   */
  public function generatePDF() {
    // Get the tickets from the previous day.
    $tickets = views_get_view_result('tickets', 'block_1');
    if (!empty($tickets)) {
      $output = [];
      $canceled_tickets = [];
      foreach ($tickets as $ticket) {
        // If ticket is canceled, continue.
        if (empty($ticket->_entity->get('status')->value)) {
          if (isset($ticket->_entity->get('seir')->value)) {
            $canceled_tickets[] = $ticket->_entity->get('seir')->value;
          }
          continue;
        }
        /** @var \Drupal\node\Entity\Node $timetable_node */
        $timetable_node = $ticket->_entity->get('timetable')->entity;
        if ($timetable_node) {
          $port_form_id = $timetable_node->get('field_timetable_from_port')->target_id;
          $port_to_id = $timetable_node->get('field_timetable_to_port')->target_id;
          if (!ticketapp_port_is_valid($port_form_id) || !ticketapp_port_is_valid($port_to_id)) {
            continue;
          }

          $document_node = $ticket->_entity->get('document')->entity;
          if (!isset($document_node)) {
            continue;
          }

          // If timetable of ticket has been uploaded then continue to the next
          // ticket.
          // @todo Check using custom table instead.
          if ($timetable_node->field_timetable_file_created->value == '1') {
            continue;
          }

          // Get only the routes that have been completed.
          if ($timetable_node->field_timetable_statuses->value !== 'complete') {
            continue;
          }

          if (!array_key_exists($timetable_node->id(), $output)) {
            $output[$timetable_node->id()] = [];
          }

          $result = $this->ticketHandler->getTicketContent($ticket->_entity);
          $output = $this->ticketHandler->countTicketsPassengers($output, $result, $timetable_node->id(), $ticket->_entity);
          $output = $this->ticketHandler->countTicketsVehicles($output, $result, $timetable_node->id(), $ticket->_entity);
        }
      }
      $timetable_results = $output;
      // Find first and last series code for each type of timetable.
      if (!empty($timetable_results)) {
        $timetable_results = $this->ticketHandler->getSeiriesByType($timetable_results);
      }

      if (!empty($timetable_results)) {
        // Make sure the required folder exists and is writable.
        $directory = file_directory_temp() . $this->directory;
        if (!$this->fileSystem->prepareDirectory($directory, $this->fileSystem::MODIFY_PERMISSIONS | $this->fileSystem::CREATE_DIRECTORY)) {
          $this->logger->error("Couldn't create directory @path or change its permissions.", [
            '@path' => $directory,
          ]);
          return FALSE;
        }

        $directory = $this->publicDirectory;
        if (!$this->fileSystem->prepareDirectory($directory, $this->fileSystem::MODIFY_PERMISSIONS | $this->fileSystem::CREATE_DIRECTORY)) {
          $this->logger->error("Couldn't create directory @path or change its permissions.", [
            '@path' => $directory,
          ]);
          return FALSE;
        }

        /*
         * Pdf Debugging Instructions.
         * 1) Comment all the following foreach loops
         * 2) Comment the if statements in "Get only the routes that have been
         *    completed" and "If timetable of ticket has been uploaded then
         *    continue to the next ticket".
         * 3) Call the function
         *    @code
         *    $this->pdfDebug($timetable_results, $some_id_in_timetable_results)
         *    @enccode
         * 4) Run /pdf-download
         * $this->pdfDebug($timetable_results,3816);
         */

        foreach ($timetable_results as $timetable_id => $timetable_item) {
          // @todo The way the $timetable_node variable is used in the following
          //   code is a bit weird considering it gets its value from the
          //   previous foreach{} loop.
          try {
            $mpdf = new Mpdf();
            $html_first_table = $this->tablePassengers($timetable_item);
            $html_second_table = $this->tableVehicles($timetable_item);
            $html_third_table = $this->tableCanceledTicket($canceled_tickets);
            $mpdf->WriteHTML($html_first_table);
            $mpdf->AddPage();
            $mpdf->WriteHTML($html_second_table);
            $mpdf->AddPage();
            $mpdf->WriteHTML($html_third_table);
            $timetable_node = $this->entityManager->getStorage('node')
              ->load($timetable_id);
            $port_from_id = $timetable_node->get('field_timetable_from_port')->target_id;
            $port_from_node = $this->entityManager->getStorage('node')
              ->load($port_from_id);
            $mpdf->Output(file_directory_temp() . $this->directory . '/' . Support::greekToLatin($port_from_node->get('title')->value) . date('Hi', $timetable_item['general_information']['departure_time']) . $timetable_id . '.pdf', Destination::FILE);
            // Update field_timetable_file_created to TRUE as we created
            // timetable's pdf file.
            $timetable_node->get('field_timetable_file_created')->value = 1;
            $timetable_node->save();
          }
          catch (\Exception $e) {
            watchdog_exception('ticketapp_tablepdf', $e);
          }
        }
        $directory_path = file_directory_temp() . $this->directory;
        $tempfiles = file_scan_directory($directory_path, '/.*/');
        foreach ($tempfiles as $tempfile) {
          try {
            $filesaved_uri = $this->fileSystem->copy($tempfile->uri, "{$this->publicDirectory}/{$tempfile->filename}");
            $file = $this->entityManager->getStorage('file')->create([
              'uri' => $filesaved_uri,
            ]);
            $file->save();
            // Get last file node id.
            $nids = $this->entityManager->getStorage('node')->getQuery()
              ->condition('type', 'files')
              ->execute();
            if ($nids) {
              $create_new_file = TRUE;
              foreach ($nids as $nid) {
                /** @var \Drupal\node\Entity\Node $node */
                $node = $this->entityManager->getStorage('node')->load($nid);
                if ($node->getTitle() === date('d-m-Y') && $node->field_files_type->value === 'port_authority') {
                  $node->field_geniko_archeio[] = [
                    'target_id' => $file->id(),
                    'display' => '1',
                    'description' => '',
                  ];
                  $node->save();
                  $create_new_file = FALSE;
                  break;
                }
              }
              if ($create_new_file) {
                $node = $this->entityManager->getStorage('node')->create([
                  'type' => 'files',
                  'field_geniko_archeio' => $file->id(),
                  'field_files_type' => 'port_authority',
                  'created' => time(),
                ]);
                $node->save();
              }

            }
            else {
              $node = $this->entityManager->getStorage('node')->create([
                'type' => 'files',
                'field_geniko_archeio' => $file->id(),
                'field_files_type' => 'port_authority',
                'created' => time(),
              ]);
              $node->save();
            }
            $this->logger->info('Pdf file @file_name was uploaded successfully.', [
              '@file_name' => $tempfile->filename,
            ]);
            // File was created so we delete it from the temporary tablepdf
            // directory. If not we can see which file wasn't uploaded.
            $this->fileSystem->delete($tempfile->uri);
          }
          catch (\Exception $e) {
            watchdog_exception('ticketapp_tablexlsx', $e);
          }
        }
      }
    }

    exit('OK');
  }

  // 1, 2, 3 are Greek character.

  /**
   * Generates the HTML for the passengers table.
   *
   * @param array $timetable_item
   *   The timetable item to process.
   *
   * @return string
   *   Returns the HTML of the table.
   */
  private function tablePassengers(array $timetable_item) {
    $html = '
      <style>
      table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
      }
      
      td, th {
        border: 3px solid #dddddd;
        text-align: left;
        padding: 8px;
      }
      
      th {
        background: #83BDE3;
      }
      
      </style> 
      <table>
        <tr>
            <td colspan="15"><strong>ΜΗΧΑΝΟΓΡΑΦΙΚΗ ΚΑΤΑΣΤΑΣΗ ΕΚΔΟΘΕΝΤΩΝ ΕΙΣΙΤΗΡΙΩΝ ΕΠΙΒΑΤΩΝ</strong></td>
        </tr>
        <tr>
          <th>ΠΛΟΙΟΚΤΗΤΡΙΑ ΕΤΑΙΡΕΙΑ</th> 
          <td colspan="14">THASSOS FERRIES - ΚΟΙΝΟΠΡΑΞΙΑ Ε/Γ - Ο/Γ ΘΑΣΟΥ</td>
        </tr>
        <tr>
          <th>ΟΝ/ΜΟ - ΑΡ. ΜΗΤΡΩΟΥ ΝΑΥΤ. ΠΡΑΚΤΟΡΑ</th>
          <td colspan="14">ΙΩΑΝΝΗΣ ΜΗΤΣΟΠΟΥΛΟΣ ' . $timetable_item['general_information']['agent_id'] . '</td>
        </tr>
        <tr>
          <th>ΔΙΑΔΡΟΜΗ</th>
          <td colspan="14">' . $timetable_item['general_information']['name_of_port_from'] . '-' . $timetable_item['general_information']['name_of_port_to'] . '</td>
        </tr>
        <tr>
          <th>ΠΛΟΙΟ</th>
          <td colspan="14">' . $timetable_item['general_information']['ship_name'] . '</td>
        </tr>
        <tr>
          <th>ΗΜΕΡΟΜΗΝΙΑ</th>
          <td colspan="14">' . date('d/m/Y', $timetable_item['general_information']['departure_time']) . '</td>
        </tr>
        <tr>
          <th>ΩΡΑ</th>
          <td colspan="14">' . date('H:i', $timetable_item['general_information']['departure_time']) . '</td>
        </tr>
        <tr>
          <th>ΧΕΙΡΙΣΤΗΣ</th>
          <td colspan="4">1</td>
          <td colspan="4">2</td>
          <td colspan="4">3</td>
          <td colspan="2">ΣΥΝΟΛΟ</td>
        </tr>
        <tr>
          <th rowspan="14">ΕΠΙΒΑΤΕΣ (ΟΙΚΟΝΟΜΙΚΗ ΘΕΣΗ)</th>
          <td rowspan="2">ΟΛΟΚΛΗΡΑ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['1']['full_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['1']['full_from'] . '</td>
          <td rowspan="2">ΟΛΟΚΛΗΡΑ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['2']['full_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['2']['full_from'] . '</td>
          <td rowspan="2">ΟΛΟΚΛΗΡΑ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['3']['full_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['3']['full_from'] . '</td>
          <td rowspan="2">ΟΛΟΚΛΗΡΑ</td>
          <td rowspan="2">' . (int) ($timetable_item['tickets_numbers']['1']['full_count'] + $timetable_item['tickets_numbers']['2']['full_count'] + $timetable_item['tickets_numbers']['3']['full_count']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['full_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['full_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['full_to'] . '</td>
        </tr>
        <tr>
          <td rowspan="2">ΓΚΡΟΥΠ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['1']['group_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['1']['group_from'] . '</td>
          <td rowspan="2">ΓΚΡΟΥΠ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['2']['group_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['2']['group_from'] . '</td>
          <td rowspan="2">ΓΚΡΟΥΠ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['3']['group_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['3']['group_from'] . '</td>
          <td rowspan="2">ΓΚΡΟΥΠ</td>
          <td rowspan="2">' . (int) ($timetable_item['tickets_numbers']['1']['group_count'] + $timetable_item['tickets_numbers']['2']['group_count'] + $timetable_item['tickets_numbers']['3']['group_count']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['group_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['group_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['group_to'] . '</td>
        </tr>
        <tr>
          <td rowspan="2">ΕΚΠΤΩΣΗ 50%</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['1']['discount_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['1']['discount_from'] . '</td>
          <td rowspan="2">ΕΚΠΤΩΣΗ 50%</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['2']['discount_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['2']['discount_from'] . '</td>
          <td rowspan="2">ΕΚΠΤΩΣΗ 50%</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['3']['discount_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['3']['discount_from'] . '</td>
          <td rowspan="2">ΕΚΠΤΩΣΗ 50%</td>
          <td rowspan="2">' . (int) ($timetable_item['tickets_numbers']['1']['discount_count'] + $timetable_item['tickets_numbers']['2']['discount_count'] + $timetable_item['tickets_numbers']['3']['discount_count']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['discount_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['discount_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['discount_to'] . '</td>
        </tr>
        <tr>
          <td rowspan="2">ΠΑΙΔΙΑ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['1']['child_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['1']['child_from'] . '</td>
          <td rowspan="2">ΠΑΙΔΙΑ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['2']['child_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['2']['child_from'] . '</td>
          <td rowspan="2">ΠΑΙΔΙΑ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['3']['child_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['3']['child_from'] . '</td>
          <td rowspan="2">ΠΑΙΔΙΑ</td>
          <td rowspan="2">' . (int) ($timetable_item['tickets_numbers']['1']['child_count'] + $timetable_item['tickets_numbers']['2']['child_count'] + $timetable_item['tickets_numbers']['3']['child_count']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['child_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['child_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['child_to'] . '</td>
        </tr>
        <tr>
          <td rowspan="2">ΜΩΡΑ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['1']['baby_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['1']['baby_from'] . '</td>
          <td rowspan="2">ΜΩΡΑ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['2']['baby_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['2']['baby_from'] . '</td>
          <td rowspan="2">ΜΩΡΑ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['3']['baby_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['3']['baby_from'] . '</td>
          <td rowspan="2">ΜΩΡΑ</td>
          <td rowspan="2">' . (int) ($timetable_item['tickets_numbers']['1']['baby_count'] + $timetable_item['tickets_numbers']['2']['baby_count'] + $timetable_item['tickets_numbers']['3']['baby_count']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['baby_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['baby_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['baby_to'] . '</td>
        </tr>
        <tr>
          <td rowspan="2">ΟΙΚΟΓΕΝΕΙΑΚΟ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['1']['family_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['1']['family_from'] . '</td>
          <td rowspan="2">ΟΙΚΟΓΕΝΕΙΑΚΟ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['2']['family_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['2']['family_from'] . '</td>
          <td rowspan="2">ΟΙΚΟΓΕΝΕΙΑΚΟ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['3']['family_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['3']['family_from'] . '</td>
          <td rowspan="2">ΟΙΚΟΓΕΝΕΙΑΚΟ</td>
          <td rowspan="2">' . (int) ($timetable_item['tickets_numbers']['1']['family_count'] + $timetable_item['tickets_numbers']['2']['family_count'] + $timetable_item['tickets_numbers']['3']['family_count']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['family_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['family_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['family_to'] . '</td>
        </tr>
        <tr>
          <td rowspan="2">ΣΧΟΛΙΚΟ ΓΚΡΟΥΠ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['1']['school_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['1']['school_from'] . '</td>
          <td rowspan="2">ΣΧΟΛΙΚΟ ΓΚΡΟΥΠ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['2']['school_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['2']['school_from'] . '</td>
          <td rowspan="2">ΣΧΟΛΙΚΟ ΓΚΡΟΥΠ</td>
          <td rowspan="2">' . $timetable_item['tickets_numbers']['3']['school_count'] . '</td>
          <td>ΑΠΟ</td>
          <td>' . $timetable_item['general_information']['from_to']['3']['school_from'] . '</td>
          <td rowspan="2">ΣΧΟΛΙΚΟ ΓΚΡΟΥΠ</td>
          <td rowspan="2">' . (int) ($timetable_item['tickets_numbers']['1']['school_count'] + $timetable_item['tickets_numbers']['2']['school_count'] + $timetable_item['tickets_numbers']['3']['school_count']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['school_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['school_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['school_to'] . '</td>
        </tr>
        <tr>
           <td rowspan="2" colspan="5">&nbsp;</td>
           <td colspan="10">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="5">ΘΑΣΟΣ, ' . date('d/m/Y', $timetable_item['general_information']['departure_time']) . '</td>
            <td colspan="5">&nbsp;</td>
        </tr>
        <tr>
          <td height="100" colspan="5">&nbsp;</td>
          <td height="100" colspan="5">ΥΠΟΓΡΑΦΗ ΝΑΥΤΙΚΟΥ ΠΡΑΚΤΟΡΑ</td>
          <td height="100" colspan="5">&nbsp;</td>
        </tr>
        <tr>
          <td height="100" colspan="5">&nbsp;</td>
          <td height="100" colspan="5">ΗΜΕΡΟΜΗΝΙΑ - ΩΡΑ ΥΠΟΒΟΛΗΣ</td>
          <td height="100" colspan="5">&nbsp;</td>
        </tr>
        <tr>
          <td height="100" colspan="5">&nbsp;</td>
          <td height="100" colspan="5">Ο ΒΕΒΑΙΩΝ</td>
          <td height="100" colspan="5">&nbsp;</td>
        </tr>
      </table>
      ';
    return $html;
  }

  // 1, 2, 3 are Greek character

  /**
   * Generats the HTML table for the vehicles.
   *
   * @param array $timetable_item
   *   The timetable item to process.
   *
   * @return string
   *   The HTML of the table.
   */
  private function tableVehicles(array $timetable_item) {
    $html = '
      <style>
      table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
      }
      
      td, th {
        border: 3px solid #dddddd;
        text-align: left;
        padding: 8px;
      }
      
      th {
        background: #83BDE3;
      }
      
      </style> 
      <table>
        <tr>
            <td colspan="15"><strong>ΜΗΧΑΝΟΓΡΑΦΙΚΗ ΚΑΤΑΣΤΑΣΗ ΕΚΔΟΘΕΙΣΩΝ ΑΠΟΔΕΙΞΕΩΝ ΜΕΤΑΦΟΡΑΣ ΟΧΗΜΑΤΩΝ</strong></td>
        </tr>
        <tr>
          <th>ΧΕΙΡΙΣΤΗΣ</th>
          <td colspan="4">1</td>
          <td colspan="4">2</td>
          <td colspan="4">3</td>
          <td colspan="2">ΣΥΝΟΛΟ</td>
        </tr>
        <tr>
            <th rowspan="4">ΟΧΗΜΑΤΑ</th>
            <td rowspan="2">ΜΙΚΡΑ <4,25</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['car_small'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['car_small_from'] . '</td>
            <td rowspan="2">ΜΙΚΡΑ <4,25</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['car_small'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['car_small_from'] . '</td>
            <td rowspan="2">ΜΙΚΡΑ <4,25</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['car_small'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['car_small_from'] . '</td>
            <td rowspan="2">ΜΙΚΡΑ <4,25</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['car_small'] + $timetable_item['vehicles_numbers']['2']['car_small'] + $timetable_item['vehicles_numbers']['3']['car_small']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['car_small_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['car_small_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['car_small_to'] . '</td>
        </tr>
        <tr>
            <td rowspan="2">ΜΕΓΑΛΑ >4,25</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['car_large'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['car_large_from'] . '</td>
            <td rowspan="2">ΜΕΓΑΛΑ >4,25</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['car_large'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['car_large_from'] . '</td>
            <td rowspan="2">ΜΕΓΑΛΑ >4,25</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['car_large'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['car_large_from'] . '</td>
            <td rowspan="2">ΜΕΓΑΛΑ >4,25</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['car_large'] + $timetable_item['vehicles_numbers']['2']['car_large'] + $timetable_item['vehicles_numbers']['3']['car_large']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['car_large_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['car_large_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['car_large_to'] . '</td>
        </tr>
        <tr>
            <th rowspan="4">ΔΙΚΥΚΛΑ</th>
            <td rowspan="2">ΜΕΧΡΙ 250cc</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['bike_small'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['bike_small_from'] . '</td>
            <td rowspan="2">ΜΕΧΡΙ 250cc</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['bike_small'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['bike_small_from'] . '</td>
            <td rowspan="2">ΜΕΧΡΙ 250cc</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['bike_small'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['bike_small_from'] . '</td>
            <td rowspan="2">ΜΕΧΡΙ 250cc</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['bike_small'] + $timetable_item['vehicles_numbers']['2']['bike_small'] + $timetable_item['vehicles_numbers']['3']['bike_small']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['bike_small_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['bike_small_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['bike_small_to'] . '</td>
        </tr>
        <tr>
            <td rowspan="2">ΑΝΩ ΤΩΝ 250cc</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['bike_large'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['bike_large_from'] . '</td>
            <td rowspan="2">ΑΝΩ ΤΩΝ 250cc</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['bike_large'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['bike_large_from'] . '</td>
            <td rowspan="2">ΑΝΩ ΤΩΝ 250cc</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['bike_large'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['bike_large_from'] . '</td>
            <td rowspan="2">ΑΝΩ ΤΩΝ 250cc</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['bike_large'] + $timetable_item['vehicles_numbers']['2']['bike_large'] + $timetable_item['vehicles_numbers']['3']['bike_large']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['bike_large_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['bike_large_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['bike_large_to'] . '</td>
        </tr>
        <tr>
            <th rowspan="2">ΠΟΔΗΛΑΤΑ</th>
            <td rowspan="2">ΠΟΔΗΛΑΤΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['bicycle'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['bicycle_from'] . '</td>
            <td rowspan="2">ΠΟΔΗΛΑΤΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['bicycle'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['bicycle_from'] . '</td>
            <td rowspan="2">ΠΟΔΗΛΑΤΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['bicycle'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['bicycle_from'] . '</td>
            <td rowspan="2">ΠΟΔΗΛΑΤΑ</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['bicycle'] + $timetable_item['vehicles_numbers']['2']['bicycle'] + $timetable_item['vehicles_numbers']['3']['bicycle']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['bicycle_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['bicycle_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['bicycle_to'] . '</td>
        </tr>
        <tr>
            <th rowspan="2">ΦΟΡΤΗΓΑ</th>
            <td rowspan="2">ΦΟΡΤΗΓΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['truck'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['truck_from'] . '</td>
            <td rowspan="2">ΦΟΡΤΗΓΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['truck'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['truck_from'] . '</td>
            <td rowspan="2">ΦΟΡΤΗΓΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['truck'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['truck_from'] . '</td>
            <td rowspan="2">ΦΟΡΤΗΓΑ</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['truck'] + $timetable_item['vehicles_numbers']['2']['truck'] + $timetable_item['vehicles_numbers']['3']['truck']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['truck_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['truck_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['truck_to'] . '</td>
        </tr>
        <tr>
            <th rowspan="2">ΛΕΩΦΟΡΕΙΑ</th>
            <td rowspan="2">ΛΕΩΦΟΡΕΙΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['bus'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['bus_from'] . '</td>
            <td rowspan="2">ΛΕΩΦΟΡΕΙΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['bus'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['bus_from'] . '</td>
            <td rowspan="2">ΛΕΩΦΟΡΕΙΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['bus'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['bus_from'] . '</td>
            <td rowspan="2">ΛΕΩΦΟΡΕΙΑ</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['bus'] + $timetable_item['vehicles_numbers']['2']['bus'] + $timetable_item['vehicles_numbers']['3']['bus']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['bus_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['bus_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['bus_to'] . '</td>
        </tr>
        <tr>
            <th rowspan="2">ΦΟΡΤΩΤΙΚΗ</th>
            <td rowspan="2">ΦΟΡΤΩΤΙΚΗ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['truck_trailer'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['truck_trailer_from'] . '</td>
            <td rowspan="2">ΦΟΡΤΩΤΙΚΗ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['truck_trailer'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['truck_trailer_from'] . '</td>
            <td rowspan="2">ΦΟΡΤΩΤΙΚΗ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['truck_trailer'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['truck_trailer_from'] . '</td>
            <td rowspan="2">ΦΟΡΤΩΤΙΚΗ</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['truck_trailer'] + $timetable_item['vehicles_numbers']['2']['truck_trailer'] + $timetable_item['vehicles_numbers']['3']['truck_trailer']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['truck_trailer_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['truck_trailer_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['truck_trailer_to'] . '</td>
        </tr>
        <tr>
            <th rowspan="2">ΒΥΤΙΑ</th>
            <td rowspan="2">ΒΥΤΙΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['truck_tank'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['truck_tank_from'] . '</td>
            <td rowspan="2">ΒΥΤΙΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['truck_tank'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['truck_tank_from'] . '</td>
            <td rowspan="2">ΒΥΤΙΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['truck_tank'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['truck_tank_from'] . '</td>
            <td rowspan="2">ΒΥΤΙΑ</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['truck_tank'] + $timetable_item['vehicles_numbers']['2']['truck_tank'] + $timetable_item['vehicles_numbers']['3']['truck_tank']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['truck_tank_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['truck_tank_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['truck_tank_to'] . '</td>
        </tr>
        <tr>
            <th rowspan="2">ΔΚΕΑ</th>
            <td rowspan="2">ΔΚΕΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['packs'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['packs_from'] . '</td>
            <td rowspan="2">ΔΚΕΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['packs'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['packs_from'] . '</td>
            <td rowspan="2">ΔΚΕΑ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['packs'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['packs_from'] . '</td>
            <td rowspan="2">ΔΚΕΑ</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['packs'] + $timetable_item['vehicles_numbers']['2']['packs'] + $timetable_item['vehicles_numbers']['3']['packs']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['packs_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['packs_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['packs_to'] . '</td>
        </tr>
        <tr>
            <th rowspan="2">ΜΙΚΡΟΠΩΛΗΤΕΣ</th>
            <td rowspan="2">ΜΙΚΡΟΠΩΛΗΤΕΣ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['truck_small_sellers'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['truck_small_sellers_from'] . '</td>
            <td rowspan="2">ΜΙΚΡΟΠΩΛΗΤΕΣ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['truck_small_sellers'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['truck_small_sellers_from'] . '</td>
            <td rowspan="2">ΜΙΚΡΟΠΩΛΗΤΕΣ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['truck_small_sellers'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['truck_small_sellers_from'] . '</td>
            <td rowspan="2">ΜΙΚΡΟΠΩΛΗΤΕΣ</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['truck_small_sellers'] + $timetable_item['vehicles_numbers']['2']['truck_small_sellers'] + $timetable_item['vehicles_numbers']['3']['truck_small_sellers']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['truck_small_sellers_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['truck_small_sellers_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['truck_small_sellers_to'] . '</td>
        </tr>
        <tr>
            <th rowspan="2">ΟΧΗΜΑΤΑ ΔΗΜΟΥ</th>
            <td rowspan="2">ΟΧΗΜΑΤΑ ΔΗΜΟΥ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['1']['vehicle_municipality'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['vehicle_municipality_from'] . '</td>
            <td rowspan="2">ΟΧΗΜΑΤΑ ΔΗΜΟΥ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['2']['vehicle_municipality'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['vehicle_municipality_from'] . '</td>
            <td rowspan="2">ΟΧΗΜΑΤΑ ΔΗΜΟΥ</td>
            <td rowspan="2">' . $timetable_item['vehicles_numbers']['3']['vehicle_municipality'] . '</td>
            <td>ΑΠΟ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['vehicle_municipality_from'] . '</td>
            <td rowspan="2">ΟΧΗΜΑΤΑ ΔΗΜΟΥ</td>
            <td rowspan="2">' . (int) ($timetable_item['vehicles_numbers']['1']['vehicle_municipality'] + $timetable_item['vehicles_numbers']['2']['vehicle_municipality'] + $timetable_item['vehicles_numbers']['3']['vehicle_municipality']) . '</td>
        </tr>
        <tr>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['1']['vehicle_municipality_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['2']['vehicle_municipality_to'] . '</td>
            <td>ΕΩΣ</td>
            <td>' . $timetable_item['general_information']['from_to']['3']['vehicle_municipality_to'] . '</td>
        </tr>
        <tr>
           <td rowspan="2" colspan="5">&nbsp;</td>
           <td colspan="10">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="5">ΘΑΣΟΣ, ' . date('d/m/Y', $timetable_item['general_information']['departure_time']) . '</td>
            <td colspan="5">&nbsp;</td>
        </tr>
        <tr>
          <td height="100" colspan="5">&nbsp;</td>
          <td height="100" colspan="5">ΥΠΟΓΡΑΦΗ ΝΑΥΤΙΚΟΥ ΠΡΑΚΤΟΡΑ</td>
          <td height="100" colspan="5">&nbsp;</td>
        </tr>
        <tr>
          <td height="100" colspan="5">&nbsp;</td>
          <td height="100" colspan="5">ΗΜΕΡΟΜΗΝΙΑ - ΩΡΑ ΥΠΟΒΟΛΗΣ</td>
          <td height="100" colspan="5">&nbsp;</td>
        </tr>
        <tr>
          <td height="100" colspan="5">&nbsp;</td>
          <td height="100" colspan="5">Ο ΒΕΒΑΙΩΝ</td>
          <td height="100" colspan="5">&nbsp;</td>
        </tr>
      </table>
      ';
    return $html;
  }

  /**
   * Generates a table containing the canceled tickets.
   *
   * @param array $canceled_tickets
   *   The list of canceled tickets.
   *
   * @return string
   *   Returns the HTML Table.
   */
  private function tableCanceledTicket(array $canceled_tickets) {
    $canceled_tickets = array_unique($canceled_tickets);
    $html_header = '
      <style>
      table {
        font-family: arial, sans-serif;
        border-collapse: collapse;
        width: 100%;
      }
      
      td, th {
        border: 3px solid #dddddd;
        text-align: left;
        padding: 8px;
      }
      
      th {
        background: #83BDE3;
      }
      
      </style> 
      <table>
      <tr>
        <td><strong>Aκυρωμένα Εισιτήρια</strong></td>
      </tr>';
    $html_body = '';
    foreach ($canceled_tickets as $canceled_ticket) {
      $html_body .= '<tr><td>' . $canceled_ticket . '</td></tr>';
    }
    $html_end = '</table>';
    return $html_header . $html_body . $html_end;
  }

  /**
   * Helper function to debug the PDF output.
   *
   * @param array $timetable_results
   *   The results.
   * @param int $timetable_id
   *   The id of the cancelled timetable.
   *
   * @throws \Mpdf\MpdfException
   */
  protected function pdfDebug(array $timetable_results, $timetable_id) {
    $mpdf = new Mpdf();
    $html_first_table = $this->tablePassengers($timetable_results[$timetable_id]);
    $html_second_table = $this->tableVehicles($timetable_results[$timetable_id]);
    $mpdf->WriteHTML($html_first_table);
    $mpdf->AddPage();
    $mpdf->WriteHTML($html_second_table);
    $mpdf->AddPage();
    $mpdf->Output();
  }

}
