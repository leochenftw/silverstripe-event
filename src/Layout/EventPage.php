<?php

namespace Leochenftw\SSEvent\Layout;
use Leochenftw\SSEvent\Model\EventLocation;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Member;
use SilverStripe\Forms\DatetimeField;
use SilverStripe\Assets\Image;
use SilverShop\HasOneField\HasOneButtonField;
use Leochenftw\SSEvent\Model\RSVP;
use \SilverStripe\Forms\GridField\GridField;
use \SilverStripe\Forms\GridField\GridFieldConfig_Base;
use \SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use \SilverStripe\Forms\GridField\GridFieldConfig_RecordEditor;
use Bummzack\SortableFile\Forms\SortableUploadField;
use Page;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class EventPage extends Page
{
    private static $icon = 'leochenftw/silverstripe-event: client/img/event.png';
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'EventPage';
    private static $description = 'Like the name says: an event Page :)';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'QRToken'       =>  'Varchar(40)',
        'EventStart'    =>  'Datetime',
        'EventEnd'      =>  'Datetime',
        'AttendeeLimit' =>  'Int',
        'AllowGuests'   =>  'Boolean'
    ];

    public function populateDefaults()
    {
        $this->QRToken  =   sha1(time() . mt_rand() . mt_rand());
    }

    private static $indexes = [
        'QRToken'   =>  [
            'type'  =>  'unique'
        ]
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'FeaturedImage' =>  Image::class,
        'Location'      =>  EventLocation::class
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'RSVPs'     =>  RSVP::class
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'EventPhotos'   =>  Image::class
    ];

    /**
     * Defines Database fields for the Many_many bridging table
     * @var array
     */
    private static $many_many_extraFields = [
        'EventPhotos' => [
            'SortOrder' => 'Int'
        ]
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->fieldByName('Root.Main.Title')->setDescription('QR Code: ' . $this->AbsoluteLink() . 'turnup/' . $this->QRToken);
        $fields->addFieldsToTab(
            'Root.Main',
            [
                UploadField::create(
                    'FeaturedImage',
                    'FeaturedImage'
                ),
                DatetimeField::create(
                    'EventStart',
                    'Start'
                ),
                DatetimeField::create(
                    'EventEnd',
                    'End'
                ),
                TextField::create(
                    'AttendeeLimit',
                    'Attendee Limit'
                )->setDescription('0 means no limit.'),
                CheckboxField::create(
                    'AllowGuests',
                    'Allow Guests'
                ),
                HasOneButtonField::create($this, "Location")
            ],
            'URLSegment'
        );

        $fields->addFieldToTab(
            'Root.RSVPs',
            $gf = GridField::create('RSVPs', 'RSVPs', $this->RSVPs())
        );

        if (Member::currentUser() && Member::currentUser()->isDefaultadmin()) {
            $gf->setConfig(GridFieldConfig_RecordEditor::create());
        } else {
            $gf->setConfig(GridFieldConfig_RecordViewer::create());
        }

        $fields->addFieldToTab(
            'Root.EventPhotos',
            SortableUploadField::create(
                'EventPhotos', 'Event photo gallery'
            )->setDescription('Photos taken during the event')
        );

        return $fields;
    }

    public function has_enough_seats($n, $rsvp)
    {
        return $this->AttendeeLimit - $this->get_total_attendee_count($rsvp) - $n >= 0;
    }

    public function get_total_attendee_count($exclude)
    {
        $n      =   0;
        if ($exclude->exists()) {
            $rsvps  =   $this->RSVPs()->exclude(['ID' => $exclude->ID]);
        } else {
            $rsvps  =   $this->RSVPs();
        }

        foreach ($rsvps as $rsvp) {
            $n += ($rsvp->NumGuests + 1);
        }

        return $n;
    }

    public function already_signed_up()
    {
        if ($member = Member::currentUser()) {
            return $this->RSVPs()->filter(['MemberID' => $member->ID])->first();
        }

        return false;
    }
}