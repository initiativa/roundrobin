<?php

class PluginTicketBalanceITILCategoryHookHandler extends CommonDBTM implements IPluginTicketBalanceHookItemHandler {

    public function itemAdded(CommonDBTM $item) {
        PluginTicketBalanceLogger::addWarning(__METHOD__ . " - Item Type: " . $item->getType());
        if ($item->getType() !== 'ITILCategory') {
            return;
        }
        PluginTicketBalanceLogger::addWarning(__METHOD__ . " - ITILCategoryId: " . $this->getItilCategoryId($item));
        $rrAssignmentsEntity = new PluginTicketBalanceRRAssignmentsEntity();
        $rrAssignmentsEntity->insertItilCategory($this->getItilCategoryId($item));
    }

    protected function getItilCategoryId(CommonDBTM $item) {
        return $item->fields['id'];
    }

    public function itemDeleted(CommonDBTM $item) {
        PluginTicketBalanceLogger::addWarning(__METHOD__ . " - Item Type: " . $item->getType());
        if ($item->getType() !== 'ITILCategory') {
            return;
        }
        PluginTicketBalanceLogger::addWarning(__METHOD__ . " - ITILCategoryId: " . $this->getItilCategoryId($item));
        $rrAssignmentsEntity = new PluginTicketBalanceRRAssignmentsEntity();
        $rrAssignmentsEntity->updateIsActive($this->getItilCategoryId($item), 0);
    }

    public function itemPurged(CommonDBTM $item) {
        PluginTicketBalanceLogger::addWarning(__METHOD__ . " - Item Type: " . $item->getType());
        if ($item->getType() !== 'ITILCategory') {
            return;
        }
        PluginTicketBalanceLogger::addWarning(__METHOD__ . " - ITILCategoryId: " . $this->getItilCategoryId($item));
        $rrAssignmentsEntity = new PluginTicketBalanceRRAssignmentsEntity();
        $rrAssignmentsEntity->deleteItilCategory($this->getItilCategoryId($item));
    }

}
