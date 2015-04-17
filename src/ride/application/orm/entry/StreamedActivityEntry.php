<?php

namespace ride\application\orm\entry;

use ride\application\orm\entry\StreamActivityEntry;

/**
 * Interface for an entry which is part of the activity stream
 */
interface StreamedActivityEntry {

    /**
     * Sets the activity in the stream
     * @param \ride\application\orm\entry\StreamActivityEntry $streamActivity
     * @return null
     */
    public function setStreamActivity(StreamActivityEntry $streamActivity);

    /**
     * Gets the activity in the stream
     * @return \ride\application\orm\entry\StreamActivityEntry|null
     */
    public function getStreamActivity();

    /**
     * Checks whether this entry should be included in the stream
     * @return boolean
     */
    public function isStreamedActivity();

    /**
     * Populates the custom fields on a stream activity
     * @param \ride\application\orm\entry\StreamActivityEntry $streamActivity
     * @return null
     */
    public function populateStreamActivity(StreamActivityEntry $streamActivity);

}
