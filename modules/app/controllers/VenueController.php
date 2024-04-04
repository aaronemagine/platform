// modules/controllers/VenueController.php
namespace app\controllers;

use Craft;
use craft\web\Controller;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use Throwable;

class VenueController extends Controller
{
    public function actionEdit()
    {
        $venueId = Craft::$app->getRequest()->getQueryParam('venueId');
        if ($venueId) {
            $venue = Craft::$app->getEntries()->getEntryById($venueId);
            if (!$venue) {
                throw new ElementNotFoundException('Venue not found');
            }
        } else {
            $venue = new Entry();
            $venue->sectionId = Craft::$app->getSections()->getSectionByHandle('venues')->id;
            $venue->typeId = Craft::$app->getSections()->getSectionByHandle('venues')->getEntryTypes()[0]->id;
        }

        return $this->renderTemplate('edit-venue', [
            'venue' => $venue,
        ]);
    }
}
