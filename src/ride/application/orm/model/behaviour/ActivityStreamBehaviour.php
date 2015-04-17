<?php

namespace ride\application\orm\model\behaviour;

use ride\application\orm\entry\StreamActivityEntry;
use ride\application\orm\entry\StreamedActivityEntry;

use ride\library\cms\exception\CmsException;
use ride\library\orm\model\behaviour\AbstractBehaviour;
use ride\library\orm\model\Model;

/**
 * Behaviour to create an activity stream from different models
 */
class ActivityStreamBehaviour extends AbstractBehaviour {

    /**
     * Hook before inserting an entry
     * @param \ride\library\orm\model\Model $model
     * @param mixed $entry
     * @return null
     */
    public function postInsert(Model $model, $entry) {
        if (!$entry instanceof StreamedActivityEntry) {
            return;
        }

        $entry->setStreamActivity($this->getStreamActivityOnEntry($model, $entry));

        $model->save($entry);
    }

    /**
     * Hook before updating an entry
     * @param \ride\library\orm\model\Model $model
     * @param mixed $entry
     * @return null
     */
    public function postUpdate(Model $model, $entry) {
        if (!$entry instanceof StreamedActivityEntry) {
            return;
        }

        $entry->setStreamActivity($this->getStreamActivityOnEntry($model, $entry));

        $model->save($entry);
    }

    /**
     * Gets the stream activity for the provided entry
     * @param \ride\library\orm\model\Model $model
     * @param mixed $entry
     * @return \ride\application\orm\entry\StreamActivityEntry
     */
    protected function getStreamActivityOnEntry(Model $model, StreamedActivityEntry $entry) {
        $orm = $model->getOrmManager();

        // resolve locale
        if ($model->getMeta()->isLocalized()) {
            $locale = $entry->getLocale();
        } else {
            $locale = $orm->getLocale();
        }

        $url = $this->resolveEntryUrl($model, $entry, $locale);
        if (!$url) {
            return null;
        }

        $streamActivityModel = $orm->getStreamActivityModel();
        $streamActivity = $entry->getStreamActivity();

        // check if the entry is a streamed activity
        if (!$entry->isStreamedActivity()) {
            if ($streamActivity) {
                $streamActivityModel->delete($streamActivity);
            }

            return null;
        }

        // update activity stream
        if (!$streamActivity) {
            $streamActivity = $streamActivityModel->createEntry();

            $entry->setStreamActivity($streamActivity);
        }

        $streamActivity->setLocale($locale);
        $streamActivity->setUrl($url);

        $this->populateStreamActivity($model, $entry, $streamActivity);

        $streamActivityModel->save($streamActivity);

        return $streamActivity;
    }

    /**
     * Resolves the URL of the provided entry
     * @param \ride\library\orm\model\Model $model
     * @param mixed $entry
     * @param string $locale
     * @return string
     */
    protected function resolveEntryUrl(Model $model, StreamedActivityEntry $entry, $locale) {
        $orm = $model->getOrmManager();

        $cms = $orm->getDependencyInjector()->get('ride\\library\\cms\\Cms');
        $contentFacade = $orm->getDependencyInjector()->get('ride\\library\\cms\\content\\ContentFacade');
        $baseUrl = $contentFacade->getBaseUrl();

        try {
            $contentMapper = $contentFacade->getContentMapper($model->getName());
        } catch (CmsException $exception) {
            return null;
        }

        $currentSite = $cms->getCurrentSite($baseUrl);
        $url = $contentMapper->getUrl($currentSite->getId(), $locale, $entry);

        return substr($url, strlen($baseUrl));
    }

    /**
     * Sets the values of the the stream activity based on the streamed activity
     * @param \ride\library\orm\model\Model $model
     * @param \ride\application\orm\entry\StreamedActivityEntry $entry
     * @param \ride\application\orm\entry\StreamActivityEntry $streamActivity
     * @return null
     */
    protected function populateStreamActivity(Model $model, StreamedActivityEntry $entry, StreamActivityEntry $streamActivity) {
        $entryFormatter = $model->getOrmManager()->getEntryFormatter();
        $modelTable = $model->getMeta()->getModelTable();

        // set the title
        if ($modelTable->hasFormat('stream.title')) {
            $titleFormat = $modelTable->getFormat('stream.title');
        } else {
            $titleFormat = $modelTable->getFormat('title');
        }
        $streamActivity->setTitle($entryFormatter->formatEntry($entry, $titleFormat));

        // set the teaser
        if ($modelTable->hasFormat('stream.teaser')) {
            $teaserFormat = $modelTable->getFormat('stream.teaser');
        } elseif ($modelTable->hasFormat('teaser')) {
            $teaserFormat = $modelTable->getFormat('teaser');
        } else {
            $teaserFormat = null;
        }
        if ($teaserFormat) {
            $streamActivity->setTeaser($entryFormatter->formatEntry($entry, $teaserFormat));
        } else {
            $streamActivity->setTeaser(null);
        }

        // set the image
        if ($modelTable->hasFormat('stream.asset')) {
            $assetFormat = $modelTable->getFormat('stream.asset');
        } elseif ($modelTable->hasFormat('asset')) {
            $assetFormat = $modelTable->getFormat('asset');
        } else {
            $assetFormat = null;
        }
        if ($assetFormat) {
            $streamActivity->setImage($entryFormatter->formatEntry($entry, $assetFormat));
        } else {
            $streamActivity->setImage(null);
        }
    }

}
